<?php
declare(strict_types=1);
//this is a silly benchmarking synth that just fires lets you fire up to 25 notes.

class WaveformModel implements SynthInterface {
    var $dspCore;
    var $settings;
    var $buffer;
    private $oscillators;
    private $oscCount;
    private $notesHz;

    function __construct($dspCore) {
        $this->dspCore = &$dspCore;
        $this->reset();
    }
    
    public function reset() {
      $this->initSettings();
      $this->pushAllParams();
      $this->oscCount = 0;
    }

    private function initSettings() {
      $this->settings = array(
        'VOICES' => 25
      );
      file_put_contents(__DIR__ . '/defaults.json',json_encode($this->settings));
    }

    public function pushAllParams() {
      foreach($this->settings as $key=>$val) {
        $this->pushParam($key);
      }
    }

    public function setParam($name,$val) {
      //used by test-scripts so keep..
      if (!array_key_exists($name, $this->settings)) die('bad setting ' . $name);
      $this->settings[$name] = $val;
      $this->pushParam($name);
    }  

    public function parseMidi($cmd, $param1 = null, $param2 = null) {
      //nothing here. :)
    }

    private function pushParam($setting) {
      //can only be called from setParam
      $val = $this->settings[$setting];
      switch($setting) {
        case 'VOICES':
          $this->setupOscillators($val);
          break;
      }
    }

    private function setupOscillators($cnt) {
      //called on init or polyphony change
      //in C, maybe GC existing voice-objects?
      if($this->oscCount != 0) {
        //destroy every old object.
        for($i=0; $i < $this->oscCount; $i++) {
          $this->oscillators[$i] = null;
        }
      }
      $this->oscillators = array();
      //restart
      for($i=0; $i < $cnt; $i++) {
        //voice grabs the settings it needs
        $this->oscillators[$i] = new CoreOscillator($this->dspCore);
        $this->notesHz[$i] = $this->dspCore->noteToHz(rand(40,80)); 
      }
      $this->oscCount = $cnt;
    }

    public function renderNextBlock() {
      //this should be converted to stero signal.
      $bufferSize = TPH_RACK_RENDER_SIZE;
      $attenuation = 1 / $this->oscCount;
      for($i=0;$i<$bufferSize;$i++) {
          $val = 0;
          for($j=0;$j<$this->oscCount;$j++) {
            $val += $this->oscillators[$j]->getNextSample($this->notesHz[$j]);
          }
          $this->buffer[$i] = $val * $attenuation;
      }
    }
}
