<?php
declare(strict_types=1);


require(__DIR__ . '/dspCore.php');

require(__DIR__ . '/../synths/synthInterface.php');
require(__DIR__ . '/../eventors/eventorInterface.php');
require(__DIR__ . '/../effects/effectInterface.php');

//maybe this class should be split into a rack- and a pattern-player class.

class Rack {
    var $rackIdx;
    var $dspCore;        
    var $hEventor1;
    var $hEventor2;
    var $hSynth;
    var $hEffect1;
    var $hEffect2;
    var $bufferOut; 
    //
    var $pattern;         //array of pattern events
    var $ticksInPattern;  //total ticks of pattern
    var $patternPtr;      //pointer to the next element in pattern to be processed.
    var $patternTick;     //incrementing as we play.
    var $nextPattern;     //array of next pattern events (to be written)
    var $rackClock;       //absolute tick in rack, incremented and looped - on what? 24?

    function __construct($rackIdx,$dspCore, $appDir) {
        //store so we now outselfs which rack we're at.
        $this->rackIdx = $rackIdx;
        $this->appDir = $appDir;
        $this->rackRenderSize = 128;
        $this->dspCore = new DSPCore(44100 / SR_IF,440, 128, $this->appDir); 
        $this->hSynth = null;
        $this->hEventor1 = null;
        $this->hEventor2 = null;
        //
        $this->reset();
    }

    function reset() {
      $this->bufferOut = array_fill(0,$this->rackRenderSize,0);
      $this->pattern = array();
      $this->patternPtr = 0;      //needs to be reset too by master player..
      $this->patternTick = 0;     //possibly this should update event if not playing.. 
      $this->patternEOF = true;
      $this->setSwing();
  }

    function loadEventor($eventorName, $slot = 1) {
      require_once(__DIR__ . '/../eventors/' . $eventorName . '/' . $eventorName . 'Model.php');
      $class = $eventorName . 'Model';
      if ($slot != 2) {
        $this->hEventor1 = new $class($this, 1);
        return $this->hEventor1;  //not sure about this one..
      } else {
        $this->hEventor2 = new $class($this, 2);
        return $this->hEventor2;  //not sure about this one..
      }
    }

    function loadSynth($synthName) {
      require_once($this->appDir . '/src/synths/' . $synthName . '/' . $synthName . 'Model.php');
        //name of model to avoid name-conflicts?
        $class = $synthName . 'Model';
        $this->hSynth = new $class($this->dspCore);
        // should call ->reset on construct $this->hSynth->init();
    }

    function getSynthRef() {
      return $this->hSynth;
    }

    function loadEffect($effectName, $slot = 1) {
        require_once(__DIR__ . '/../effects/' . $effectName . '/' . $effectName . 'Model.php');
        $class = $effectName . 'Model';
        if ($slot != 2) {
          $this->hEffect1 = new $class($this);
          return $this->hEffect1;  //not sure about this one..
        } else {
          $this->hEffect2 = new $class($this);
          return $this->hEffect2;  //not sure about this one..
        }
    }

    function unloadEffect($slot = 1) {
      //delete[]
      if ($slot != 2) {
        $this->hEffect1 = null;
      } else {
        $this->hEffect2 = null;
      }
    }

    function loadPattern($pattern, $barCount, $signNom = 4, $signDenom = 4) {
      //here timing should be 96PPQN always.
      $this->pattern = $pattern;
      $this->ticksInPattern = $barCount * 96 * 4 * $signNom / $signDenom;
      $this->patternPtr = 0;
      $this->patternTick = 0;  //maybe this shouldn't be here when we load patterns as we go..
      $this->rackClock = 0;  //??
      $this->patternEOF = false;
      //this should really scan pass any negative start-marks.
      while($this->pattern[$this->patternPtr][0] < 0) {
        $this->patternPtr++;
      }
      $this->nextPattern = null; //??
      $this->setSwing();
    }    

    function parseMidi($command, $param1, $param2) {
      //from somewhere, (midi, screen-keyboard or pattern), a midi-event happened. Process it. 
      if (is_null($this->hEventor1)) {
        $this->hSynth->parseMidi($command, $param1, $param2);
      } else {
        $this->hEventor1->parseMidi($command, $param1, $param2);
      }
    }
    
    function processClock() {
      //playing or not, here i am.. for eventors and effect units.
      if (!is_null($this->hEventor1)) $this->hEventor1->processClock();
      if (!is_null($this->hEventor2)) $this->hEventor2->processClock();
      //not sure about effect. Rather thriggered in time by the audio playhead?
      //not sure about this..
      $this->rackClock++;
      $this->rackClock = $this->rackClock % 96; //($this->ticksInPattern / 4);
    }

    function processTick($debug = false) {
      //new tick, see if any events should be processed.
      //events in pattern must be sorted in order.
      $swingOffset = $this->calcSwingOffset();
      //not sure about this while expression, what if only one not in pattern?
      while (!$this->patternEOF && ($this->pattern[$this->patternPtr][0] + $swingOffset) <= $this->patternTick) {
          //process pattern event
        $evt = $this->pattern[$this->patternPtr];
        $cmd = $evt[1] & 0xf0;
        switch($cmd) {
          case 0x90:
            $this->parseMidi(0x90,$evt[2], $evt[3]);
            if ($this->swingDebug) echo 'sending note on at tick ' . $this->patternTick . "\n";
            break;
          case 0x80:
            $this->parseMidi(0x80,$evt[2], 0);
            if ($this->swingDebug) echo 'sending note off at tick ' . $this->patternTick . "\n";
            break;
        }
        $this->patternPtr++;
        //if we're out of bounds, reset to zero.
        //no support of negative events yet.
        if ($this->patternPtr >= sizeof($this->pattern)) {
          $this->patternEOF = true;
        }
        if ($debug) {
          echo "Swing offset: $swingOffset, PatternPtr:  $this->patternPtr, PatternTick: $this->patternTick\n";
        }
      }
      $this->patternTick++;
      if ($this->patternTick > $this->ticksInPattern) {
        $this->patternPtr = 0;
        $this->patternTick = 0;
        $this->patternEOF = false;
      }
    }


    function setSwing($time = 48, $depth = 0, $debug = false) {
      //well, this could be a class of its own. Swing should probably not be in each rack by default.
      //48 = swing distributed over 48 ticks => 16th notes.
      $this->swingTime = $time;
      $this->swingDepth = $depth; //0-1
      $this->swingDebug = $debug;
    }


    function calcSwingOffset() {
      return $this->calcSwingOffset_bin();
    }

    function calcSwingOffset_bin() {
      $swingTime = $this->swingTime;
      if ($this->patternTick % $this->swingTime == 0) {
        $swingOffset = 0;
      } else {
        $swingOffset = floor(
          ($this->swingTime - ($this->patternTick % $this->swingTime)) * 
          $this->swingDepth
        );
      }
      if ($this->swingDebug) echo 'tick ' . $this->patternTick . ', offset: ' . $swingOffset . "\r\n";
      return $swingOffset;
    }
    
    function render($blocks) {
      //blockSize = 128
      //let the synth do the work, then add the effect.
      $waveOut = array();
      for($i = 0;$i < $blocks; $i++ ) {
        $this->hSynth->renderNextBlock();
        $this->bufferOut = $this->hSynth->buffer;
        if(!is_null($this->hEffect1)) {
          $this->hEffect1->process($this->bufferOut);
        }
      }
    }

}