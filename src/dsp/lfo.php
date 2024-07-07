<?php

class LFO {
  //no custom-waves. //maybe the ramp should be built in?
  //also, a bit different since it outputs 0-127 to drive modulators. Maybe not always? Some targets have no midi-in?
  //so we could need to use float2midi like: floor((floatVal + 1) * 64) (avoid 128)
  //and for logaritmic calculations, these should be performed on input formulas.

  var $dspCore;
  var $index;
  var $angle;
  var $wtShape;
  var $sampleValue;
  var $rampValue;
  var $stepK;
  var $snhReady;
  var $snhVal;

  function __construct($dspCore) {
    $this->dspCore = &$dspCore;
    //Not always wavetables really, but sine uses it and all sizes = 1024
    $this->reset();
    $this->stepK = 1024 / $this->dspCore->sampleRate;
    $this->rampValue = 0; //??
    $this->setWaveform('sine');
  }

  function reset() {
    $this->index = 0;
    $this->angle = 0;
  }

  function setWaveform($shape) {
    //sine, tri, square, saw, s_and_h
    $this->wtShape = $shape;
  }


  function getNextSample($hz) {
    //really no point so waste math on interpolation
    switch($this->wtShape) {
      case 'sine':
        //look in oscillator wavetable
        $val = $this->dspCore->waveTables['sine'][floor($this->index)];
        break;
      case 'triangle':
        $val = ($this->index % 256)/256;
        $phase = floor($this->index / 256);
        if ($phase % 2 == 1) $val = 1 - $val;
        if ($phase >= 2) $val *= -1; 
        break;
      case 'square':
        $val = ($this->index > 512) ? -1 : 1;
        break;
      case 'sawtooth':
        $val = $this->index / 512 - 1;
        break;
      case 's_and_h':
        if ($this->snhReady) {
          if ($this->index > 512) {
            $this->snhVal = rand(-1,1);
            $this->snhReady = false;
          }
        } else {
          if ($this->index < 512) {
            $this->snhReady = true;
          }
        }
        $val = $this->snhVal;
        break;
    }
    $this->index += $hz * $this->stepK;
    while($this->index > 1024) $this->index -= 1024;
    return $val;
  }
  
}
 
