<?php
declare(strict_types=1);

require 'noiserFilter.php';

//this is a silly benchmarking synth that just fires lets you fire up to 25 notes.

class NoiserModel implements SynthInterface {
    var $dspCore;
    var $filterRef;
    var $settings;
    var $buffer;
    var $filterFreq;
    private $oscillators;
    private $oscCount;
    private $notesHz;

    function __construct($dspCore) {
        $this->dspCore = &$dspCore;
        $this->noiseOscRef = new NoiseOsc($this->dspCore);
        $this->filterRef = new ButterworthFilter();
        $this->type = 'bandpass';
        $this->reset();
    }
    
    public function reset() {
      $this->oscCount = 0;
      $this->filterFreq = 500;
      $this->filterDir = 'up';
      $this->filterRef->calculateCoefficients($this->filterFreq, 44100 / SR_IF, 6, $this->type);
    }

    private function initSettings() {
    }

    public function pushAllParams() {
    }

    public function setParam($name,$val) {
    }  

    public function parseMidi($cmd, $param1 = null, $param2 = null) {
    }

    private function pushParam($setting) {
    }

    public function renderNextBlock() {
      //this should be converted to stero signal.
      $bufferSize = $this->dspCore->rackRenderSize;
      //for($i=0;$i<$bufferSize;$i++) {
          //$this->buffer[$i] = $this->noiseOscRef->getNextSample();
      //}
      $this->buffer = $this->noiseOscRef->getSamples($bufferSize);
      //die(serialize($this->buffer));
      $this->buffer = $this->filterRef->applyFilter($this->buffer);
      if ($this->filterDir == 'up') {
        $this->filterFreq *= 1.0005;
      } else {
        $this->filterFreq *= 0.9995;
      }
      if ($this->filterDir == 'up' && $this->filterFreq > 13000) $this->filterDir = 'down';
      if ($this->filterDir == 'down' && $this->filterFreq < 250) $this->filterDir = 'up';

      $this->filterRef->calculateCoefficients($this->filterFreq, 44100, 3, $this->type);
    }
}
