<?php
declare(strict_types=1);

//this must be run in the audio thread, and optionally as background-process when app minimized.

//the eventors also need some clock that is 24PPQN. Maybe pulse only. 
class PlayerEngine {
    var $app;
    var $rackRenderSize;
    var $masterRenderSize;
    var $tempo;   //bpm
    var $timeSignNom;
    var $timeSignDenom;
    var $ticksInBeat;
    var $ticksInBar;
    var $ticksPerSec;
    var $samplesPerTick;
    var $lastTickPlayhead;
    var $bar;
    var $tick;
    var $isPlaying;
  
    function __construct($app) {
      $this->app = &$app;
      $this->rackRenderSize = $this->app->dspCore->rackRenderSize;
      $this->masterRenderSize = $this->app->dspCore->masterRenderSize;
    }

    function setTempo($tempo = 120, $timeSignNom = 4, $timeSignDenom = 4) {
      $this->tempo = $tempo; //beats per minute
      $this->timeSignNom = $timeSignNom;
      $this->timeSignDenom = $timeSignDenom;
      //tick here is *NOT* midi clock. Tick increased to 384 PPbar
      //not sure what should be here and what should be in rack.
      //but maybe all here. Why update 14 ticks..
      $this->tickToClockRatio = 4;
      $this->ticksInBar = $this->tickToClockRatio * 96 * $this->timeSignNom / $this->timeSignDenom;
      $this->ticksInBeat = $this->ticksInBar / $this->timeSignNom;
      $this->ticksPerSec = $tempo / 60 * $this->ticksInBeat;
      $this->samplesPerTick = (int) round(1/$this->ticksPerSec * $this->app->dspCore->sampleRate);
      $this->clockDivisor = 0;
    }
  
    function reset() {
      $this->bar = 0;
      $this->processTick = false;   //signal to rack that a new tick has arrived.
      $this->clock = 0;
      $this->samplesSinceClock = 0;
      $this->processClock = false;  //signal to rack (eventors) that new clock has arrived.
      $this->playhead = 0;          //samples?
      $this->lastTickPlayhead = 0;  //?
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
  
    //somewhere we also need to deal with midi-in messages.

    function renderNextBlock($debug = 0) {
      //master function for rendering and timing!
      //bufferSize = 1024
      //blockSize = 128
      $offset = 0;
      $allRacksOff = true;
      $outerEnd = $this->masterRenderSize / $this->rackRenderSize;
      $masterWave = array();
      for($outer = 0;$outer < $outerEnd; $outer++) {
        //iterate over (t)racks. USE threads MULTI-CORE HERE
        for($i=0;$i<$this->app->rackCount;$i++) {
          if (!is_null($this->app->racks[$i])) {
            $allRacksOff = false;
            if ($this->processClock) {
              //eventors & effects
              $this->app->racks[$i]->processClock();
              $this->processClock = false;
            }
            if ($this->processTick) {
              //pattern
              $this->app->racks[$i]->processTick(($debug == 20 && $outer == 4));
            }
            $wave = $this->app->racks[$i]->render(1);
          }
        }
        if ($allRacksOff) {
          for($i=0;$i<$this->rackRenderSize;$i++) {
            $wave[$i] = 0;
          }
        }
        //process all the racks through the mixer. save for later..
        for($i = 0;$i<$this->rackRenderSize;$i++) {
          $masterWave[$outer*$this->rackRenderSize + $i] = $wave[$i];
        }
        //we need this here since notes can be started within buffer size.
        $this->manageTiming();
      }
      return $masterWave;
    }

    function manageTiming() {
      //tick - running on play
      $this->processTick = false;
      if ($this->isPlaying) {
        $this->playhead += $this->rackRenderSize;
        if ($this->playhead > $this->lastTickPlayhead + $this->samplesPerTick) {
          $this->processTick = true;
          $this->lastTickPlayhead = $this->playhead;
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

    //midi here??
  }
  