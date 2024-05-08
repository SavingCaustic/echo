<?php
/* These oscillators are for audio, not LFO (ok sine). */
/* Also, noise can't be a wavetable so we need option for that */

class CoreOscillator {
  var $dspCore;
  var $index;   //aka angle
  var $waveform;
  var $wtSize;
  var $stepK;

  function __construct($dspCore) {
    $this->dspCore = &$dspCore;
    $this->reset();
    $this->setWaveform('sine'); //default
  }

  function setWaveform($waveform) {
    //probably use waveforms in core
    $this->waveform = $waveform;
    $this->wtSize = sizeof($this->dspCore->waveTables[$this->waveform]);
    $this->stepK = $this->wtSize / $this->dspCore->sampleRate;
  }

  function reset() {
    $this->index = 0;
    $this->angle = 0;
  }

  function getNextSample($hz, $fm = 0) {
    //really no point so waste math on interpolation
    return $this->getNextSample_nointer($hz, $fm);
  }

  function getNextSample_nointer($hz, $modulation = 0) {
    //extra +wtSize to cover negative support in %.
    $ix = floor(($this->wtSize + $this->index + $this->wtSize*$modulation) % $this->wtSize);
    $val = $this->dspCore->waveTables[$this->waveform][$ix];
    $this->index += $hz * $this->stepK;
    while($this->index > $this->wtSize) $this->index -= $this->wtSize;
    return $val;
  }

  function getNextSample_inter($hz) {
    //with interpolation
    $ixInt = floor($this->index);
    $ixFrac = $this->index - $ixInt;
    $ixNext = ($ixInt + 1) % $this->wtSize; 
    $val = $this->dspCore->waveTables[$this->waveform][$ixInt] * (1-$ixFrac) +
            $this->dspCore->waveTables[$this->waveform][$ixNext] * ($ixFrac) ;
    $this->index += $hz * $this->stepK;
    while($this->index > $this->wtSize) $this->index -= $this->wtSize;
    return $val;
  }
}
