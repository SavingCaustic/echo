<?php
declare(strict_types=1);

require 'noiserFilter.php';

//this is a silly benchmarking synth that just fires lets you fire up to 25 notes.

class NoiserModel implements SynthInterface {
    var $rack;
    var $dspCore;
    var $hNoiseOsc;
    var $hFilter;
    var $settings;
    var $buffer;
    var $type;
    var $filterDir;
    var $filterFreq;
    private $oscillators;
    private $oscCount;
    private $notesHz;

    function __construct($rack) {
        $this->rack = &$rack;
        $this->dspCore = $this->rack->dspCore;
        $this->buffer = &$this->rack->audioBuffer;
        $this->hNoiseOsc = new NoiseOsc($this->dspCore);
        $this->hFilter = new ButterworthFilter();
        $this->type = 'bandpass';
        $this->reset();
    }
    
    public function reset() {
      $this->oscCount = 0;
      $this->filterFreq = 5000;
      $this->filterDir = 'up';
      $this->hFilter->calculateCoefficients($this->filterFreq, TPH_SAMPLE_RATE, 2, $this->type);
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

    public function renderNextBlock(): bool {
      $this->hNoiseOsc->genSamples($this->buffer, TPH_RACK_RENDER_SIZE, true);
      //die(serialize($this->buffer));
      $this->hFilter->applyFilter($this->buffer, 1);
      if ($this->filterDir == 'up') {
        $this->filterFreq *= 1.0002;
      } else {
        $this->filterFreq *= 0.9998;
      }
      if ($this->filterDir == 'up' && $this->filterFreq > 12000) $this->filterDir = 'down';
      if ($this->filterDir == 'down' && $this->filterFreq < 2000) $this->filterDir = 'up';

      $this->hFilter->calculateCoefficients($this->filterFreq, 48000, 2, $this->type);
      return false; //mono-signal
    }
}
