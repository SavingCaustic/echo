<?php
declare(strict_types=1);

class Rack {
    var $rackIdx;
    var $synth;
    var $effect1;
    var $pattern;         //array of pattern events
    var $patternPtr;      //pointer to the next element in pattern to be processed.
    var $nextPattern;     //array of next pattern events (to be written)
    var $patternTick;     //absolute tick in pattern, incremented and looped
    var $ticksInPattern;  //total ticks of pattern
    var $dspCore;

    function __construct($rackIdx,$dspCore) {
        //store so we now outselfs which rack we're at.
        $this->rackIdx = $rackIdx;
        $this->dspCore = &$dspCore;
        $this->patternPtr = 0;   //needs to be reset too by master player..
        $this->tick = 0;          //possibly this should update event if not playing.. 
    }

    function loadSynth($synthName) {
      require_once(__DIR__ . '/../synths/' . $synthName . '/' . $synthName . 'Model.php');
        //name of model to avoid name-conflicts?
        $class = $synthName . 'Model';
        $this->synth = new $class($this->dspCore);
        $this->synth->init();
    }

    function loadEffect($effectName, $slot = 1) {
        require_once(__DIR__ . '/../effects/' . $effectName . '/' . $effectName . 'Model.php');
        $class = $effectName . 'Model';
        $this->effect1 = new $class($this->dspCore);
    }

    function loadPattern($pattern, $barCount, $signNom = 4, $signDenom = 4) {
      $this->pattern = $pattern;
      $this->ticksInPattern = $barCount * 96 * $signNom / $signDenom;
      $this->patternPtr = 0;
      $this->patternTick = 0;  //maybe this shouldn't be here when we load patterns as we go..
      //this should really scan pass any negative start-marks.
      while($this->pattern[$this->patternPtr][0] < 0) {
        $this->patternPtr++;
      }
      $this->nextPattern = null; //??
    }
    
    function getSynthRef() {
        return $this->synth;
    }

    function processTick() {
      //new tick, see if any events should be processed.
      //events in pattern must be sorted in order.
      while ($this->pattern[$this->patternPtr][0] == $this->patternTick) {
        //process pattern event
        $evt = $this->pattern[$this->patternPtr];
        $cmd = $evt[1] & 0xf0;
        switch($cmd) {
          case 0x90:
            $this->synth->noteOn($evt[2], $evt[3]);
            break;
          case 0x80:
            $this->synth->noteOff($evt[2], $evt[3]);
            break;
        }
        $this->patternPtr++;
        //if we're out of bounds, reset to zero.
        //no support of negative events yet.
        if ($this->patternPtr >= sizeof($this->pattern)) $this->patternPtr = 0;
      }
      $this->patternTick++;
      $this->patternTick %= $this->ticksInPattern;
    }

    function render($blocks) {
      //blockSize = 128
      //let the synth do the work, then add the effect.
      $waveOut = array();
      for($i = 0;$i < $blocks; $i++ ) {
        $this->synth->renderNextBlock();
        if(!is_null($this->effect1)) {
          $wave = $this->effect1->process($this->synth->buffer);
        } else {
          $wave = $this->synth->buffer;
        }
        $waveOut = array_merge($waveOut, $wave);
      }
      return $waveOut;
  }

}