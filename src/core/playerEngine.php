<?php
declare(strict_types=1);
require('rack.php');

//this must be run in the audio thread, and optionally as background-process when app minimized.

class PlayerEngine {
    var $settings;            //key-val high-level settings
    var $appDir;
    //
    var $rackRefs;            //racks
    var $masterRef = null;    //mixer(?) and reverb
    var $sequencerRef = null; //sequencer
    //
    var $rackCount;
    var $rackRenderSize;
    var $masterRenderSize;
    //
    var $playMode;
    var $tempo;   //bpm
    var $timeSignNom;
    var $timeSignDenom;
    var $ticksInBeat;
    var $ticksInBar;
    var $ticksPerSec;
    var $samplesPerTick;
    var $lastTickPlayhead;
    //
    var $bar;
    var $tick;
    var $isPlaying;
    var $processClock;
    var $samplesSinceClock;
    var $processTick;
    
    function __construct() {
      require('../appdir.php');
      $this->appDir = getAppDir();
      $this->rackRenderSize = 128;
      $this->masterRenderSize = 1024;
      $this->rackCount = 8;
      $this->rackRefs = array();
      //perhaps metronome should sit at rack 0.
      for($i=0; $i < $this->rackCount; $i++) {
        $this->rackRefs[$i] = null;
      }
    }

    function close() {
      //dunno. clean up everything, then quit.
    }

    function reset() {
      //When requested? Does it clear all the racks or just resets the player?
      $this->bar = 0;
      $this->processTick = false;   //signal to rack that a new tick has arrived.
      $this->clock = 0;
      $this->samplesSinceClock = 0;
      $this->processClock = false;  //signal to rack (eventors) that new clock has arrived.
      $this->playhead = 0;          //samples?
      $this->lastTickPlayhead = 0;  //?
      $this->processClock = false;
      $this->processTick = false;
      $this->samplesSinceClock = 0;
    }

    function pushAllParams() {
      //just as synth, high level settings should be distributed.
      foreach($this->settings as $key=>$val) {
        $this->setVal($key, $val) ;
      }
    }

    function setVal($key,$val) {
      switch($key) {
        case 'bpm':
          //also get time-sign and skip separate treatment for them.
          $timeSign = explode('/', $this->settings['time_sign']);
          $this->setTempo($val, $timeSign[0], $timeSign[1]);
          break;
        case 'master_tune':
          //set in dspCore. Not sure how to access it..
          //$this->dspCore->masterTune = $val;
          break;
        case 'play_mode':
          $this->playMode = 'pattern';
          break;
        case 'swing':
          //
          break;
        case 'name':
          //it's in settings. Enough?
          break;
        case 'ppqn':
          break;
        case 'time_sign':
          //dunno really..
          break;
        default:
          die('unknown song setting: ' . $key);
          break;
      }
    }

    function setTempo($tempo = 120, $timeSignNom = 4, $timeSignDenom = 4) {
      $this->tempo = $tempo; //beats per minute
      $this->timeSignNom = $timeSignNom;
      $this->timeSignDenom = $timeSignDenom;
      $this->tickToClockRatio = 4;
      $this->ticksInBar = $this->tickToClockRatio * 96 * $this->timeSignNom / $this->timeSignDenom;
      //always quarter, even 6/8 etc.
      $ticksInQuarter = $this->ticksInBar / $this->timeSignNom * $this->timeSignDenom / 4;
      $this->ticksPerSec = $tempo / 60 * $ticksInQuarter;
      $this->samplesPerTick = (int) round(1/$this->ticksPerSec * 44100 / SR_IF);
      echo 'samples per tick: ' . $this->samplesPerTick . "\n";
      $this->clockDivisor = 0;
    }
    
    function play() {
      $this->reset();               //??
      $this->isPlaying = true;
    }

    function pause() {
      //bilateral
      $this->isPlaying != $this->isPlaying;
    }

    function stop() {
      $this->isPlaying = false;
    }
  
    function rackSetup(int $rackIdx, string $synth) {
      //in c++, not really sure in how to allocate objects and best practice of controlling their lifetime.
      if (!is_null($this->rackRefs[$rackIdx])) {
          //just drop it..
          return;
      }
      $this->rackRefs[$rackIdx] = new Rack($rackIdx, $this, $this->appDir);
      $r = &$this->rackRefs[$rackIdx];
      $r->loadSynth($synth);
  }

  function getRackRef(int $rackIdx) : Rack {
      return $this->rackRefs[$rackIdx];
  }

  function setRackRenderSize($renderSize) {
    //not needed. rackRenderSize is never changed.
    $this->rackRenderSize = $renderSize;
    foreach($this->racks as $rack) {
      if (!is_null($rack)) $rack->setRackRenderSize($renderSize);
    }
  }

    //somewhere we also need to deal with midi-in messages.

    function renderNextBlock($debug = 0) {
      $offset = 0;
      $allRacksOff = true;
      $outerEnd = $this->masterRenderSize / $this->rackRenderSize;
      $masterWave = array();
      for($outer = 0;$outer < $outerEnd; $outer++) {
        //iterate over (t)racks. USE threads MULTI-CORE HERE
        for($i=0;$i<$this->rackCount;$i++) {
          if (!is_null($this->rackRefs[$i])) {
            $allRacksOff = false;
            if ($this->processClock) {
              //eventors & effects
              $this->rackRefs[$i]->processClock();
              $this->processClock = false;
            }
            if ($this->processTick) {
              //pattern
              $this->rackRefs[$i]->processTick();
            }
            //this is theaded really and goes to mixer.
            //when all are done, mixer makes the final mixdown.
            //wave can really be kept in rack instead..
            $this->rackRefs[$i]->render(1);
          }
        }
        // calculate master-wave for the rackRenderSize-block
        $wave = array_fill(0,$this->rackRenderSize,0);
        //this is more or less the mixer.
        for($i=0;$i<$this->rackCount;$i++) {
          if (!is_null($this->rackRefs[$i])) {
            for($j=0;$j<$this->rackRenderSize;$j++) {
              $wave[$j] += $this->rackRefs[$i]->bufferOut[$j];
            }
            }
        }
        //now output 
        for($i = 0;$i<$this->rackRenderSize;$i++) {
          $masterWave[$outer*$this->rackRenderSize + $i] = $wave[$i];
        }
        //we need this here since notes can be started within buffer size.
        $this->manageTiming();
      }
      return $masterWave;
    }

    function testRender($blocks = 128) {
      //move this to outside of masterPlayer
      //this is like main() for tests. Returns a wave of floats that could be converted to wav.
      //note this signal should be stereo.
      $blocks = $blocks / SR_IF;
      $waveOut = array();
      for($i=0;$i<$blocks;$i++) {
          $wave = $this->renderNextBlock($i);
          $waveOut = array_merge($waveOut, $wave);
      }
      return $waveOut;
  }


    function manageTiming() {
      //tick - running on play
      $this->processTick = false;
      if ($this->isPlaying) {
        $this->playhead += $this->rackRenderSize;
        if ($this->playhead >= $this->lastTickPlayhead + $this->samplesPerTick) {
          $this->processTick = true;
          $this->lastTickPlayhead += $this->samplesPerTick;
        }
      }
      //clock - Always runnng.
      $this->processClock = false;
      $this->samplesSinceClock += $this->samplesPerTick;
      if ($this->samplesSinceClock >= $this->samplesPerTick * 4) {
        //do stuff
        $this->processClock = true;
        $this->samplesSinceClock -= $this->samplesPerTick * 4;
      }
    }

    function manageMidiIn() {
      //reads the midi-port. 
      //forward to rack *buffer*
    }

}
