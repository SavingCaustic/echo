<?php

class Ar {
  //ar-envelope. Also used for lfo-fade in..
  var $dspCore;
  var $level;
  var $aConst;   //time to max (or to 70% if RC-simulation)
  var $rConst;
  var $mode;
  var $srInv;

  function __construct($dspCore) {
    $this->dspCore = &$dspCore;
    $this->srInv = 1000 / $dspCore->sampleRate;
  }

  function reset() {
    $this->level = 0;
    $this->aConst = 0;
    $this->rConst = 0;
  }

  function setAR($attackTime, $releaseTime, $mode = 'linear', $blockSize) {
    //if time = 1sec => 1000mS => 44100/256
    //odd forumlas needed to calculate constants based on formulas and more.
    //i really don't want blocksize here..
    $this->aConst = $attackTime;
    $this->rConst = $releaseTime;
    switch($mode) {
      case 'exp':
        //NOT TESTED
        $this->aConst = $blockSize * $this->srInv / $attackTime;
        break;
      case 'linear':
        $this->aConst = 1/44.100/($attackTime+1); //blockSize moved to getNextLevel
    }
    $this->mode = $mode;
  }

  function getNextLevel($blockSize) {
    //similar to getNextSample but since it's an envelope, we call it level.
    //just linear for now..
    //$sampleRate = 44100/256;
    //$this->aConst = 0.01;
    if($this->level < 1) {
      switch($this->mode) {
        case 'exp': //it's really exponential, no capacitor load.
          $this->level = ($this->level + 1) * $this->aConst - 1;
          break;
        case 'linear':
          $this->level += $this->aConst * $blockSize;
          break;
      }
    }
    if ($this->level > 1) $this->level = 1;
    return $this->level;
  }

}
