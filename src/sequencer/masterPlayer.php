<?php
declare(strict_types=1);

//player that iterates all racks 
//this must be run in the audio thread.

class MasterPlayer {
    //I know the tempo and more..
    //currently do not allow tempo or signature change 
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
    var $app;
  
    function __construct($app) {
      $this->app = &$app;
      $this->rackRenderSize = $this->app->dspCore->rackRenderSize;
      $this->masterRenderSize = $this->app->dspCore->masterRenderSize;
      
    }

    function setup($tempo = 120, $timeSignNom = 4, $timeSignDenom = 4) {
      $this->tempo = $tempo; //beats per minute
      $this->timeSignNom = $timeSignNom;
      $this->timeSignDenom = $timeSignDenom;
      $this->ticksInBar = 96 * $this->timeSignNom / $this->timeSignDenom;
      $this->ticksInBeat = $this->ticksInBar / $this->timeSignNom;
      $this->ticksPerSec = $tempo / 60 * $this->ticksInBeat;
      $this->samplesPerTick = (int) round(1/$this->ticksPerSec * $this->app->dspCore->sampleRate);
      //samples per tick is the real king of timing.
    }
  
    function reset() {
      $this->bar = 0;
      $this->tick = 0;
      $this->processTick = 0; //signal to rack that a new tick has arrived.
      $this->playhead = 0; //samples?
      $this->lastTickPlayhead = 0; //?
    }
  
    function play() {
      $this->reset();
      $this->isPlaying = true;
      $this->processTick = true;
    }

    function stop() {
      $this->isPlaying = false;
    }
  
    function renderNextBlock() {
      //master function for rendering and timing!
      //bufferSize = 1024
      //blockSize = 128
      $offset = 0;
      $allRacksOff = true;
      $outerEnd = $this->masterRenderSize / $this->rackRenderSize;
      $masterWave = array();
      for($outer = 0;$outer < $outerEnd; $outer++) {
        //iterate over (t)racks. USE MULTI-CORE HERE
        for($i=0;$i<$this->app->rackCount;$i++) {
          if (!is_null($this->app->racks[$i])) {
            $allRacksOff = false;
            if ($this->processTick) $this->app->racks[$i]->processTick();
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
        $this->manageTimer();
      }
      return $masterWave;
    }


    function manageTimer() {
      $this->processTick = false;
      if ($this->isPlaying) {
        $this->playhead += $this->rackRenderSize;
        if ($this->playhead > $this->lastTickPlayhead + $this->samplesPerTick) {
          $this->processTick = true;
          $this->lastTickPlayhead = $this->playhead;
        }
      }
    }
  }
  