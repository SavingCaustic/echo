<?php
require('beatnikVoice.php');
require __DIR__ . '/../../utils/wavReader.php';

//a 8-channel sample player. We add filters and stuff later. minimal now.
//voices could be static, no need for classes really.

class BeatnikModel implements SynthInterface {
  //objects
  var $dspCore;
  var $voices;
  //variables
  var $polyphony;
  var $nextBlock;
  var $buffer;
  var $bufferEmpty;
  var $debug;
  var $settings;
  //private shared (non-voice) registers
  
  function __construct($dspCore) {
    $this->dspCore = &$dspCore;
    $this->setupVoices(8);
    $this->debug = false;
    $this->reset();
  }

  public function reset() {
    $this->settings = array();
    $this->setupDefaultSamples();
    $this->bufferEmpty = 0;
    $this->pushAllParams();
  }

  
  private function setupVoices($voiceCnt) {
    //called on init or polyphony change
    //in C, maybe GC existing voice-objects?
    //voices shared (like OH/CH have *different voices* since different AR, pitch etc.
    //voices has ramp to avoid clipping
    $this->polyphony = $voiceCnt;
    for($i=0; $i < $voiceCnt; $i++) {
      //voice grabs the settings it needs
      $this->voices[$i] = new BeatnikVoice($this);
    }
  }

  function setupDefaultSamples() {
    //this should really be done by controller but lets just create something now.
    $default = array(
      'lm-2/kick.wav',
      'lm-2/snare-m.wav',
      'lm-2/hihat-closed.wav',
      'lm-2/hihat-open.wav',
      'lm-2/tom-hh.wav',
      'lm-2/tom-h.wav',
      'lm-2/tom-m.wav',
      'lm-2/tom-l.wav',
    );
    $WR = new WavReader();

    for($i=0;$i<8;$i++) {
      $data = $WR->wav2buffer($this->dspCore->appDir . '/assets/synths/beatnik/samples/' . $default[$i]);
      $this->voices[$i]->setupSample($data);
    }
  } 

  function pushAllParams() {
    //iterate over all settings and set them to respective (private) register.
    foreach($this->settings as $key=>$val) {
      $this->pushParam($key,$val);
    }
  }

  public function setParam($name,$val) {
    //used by test-scripts so keep..
    if (!array_key_exists($name, $this->settings)) die('bad setting ' . $name);
    $this->settings[$name] = $val;
    $this->pushSetting($name);
  }

  public function parseMidi($cmd, $param1 = null, $param2 = null) {
    $cmdMSN = $cmd & 0xf0;
    if ($cmdMSN == 0x90) $this->noteOn($param1, $param2);
    if ($cmdMSN == 0x80) $this->noteOff($param1, 0);
  }

  function noteOn($note, $vel) {
    //triggers fixed to C3-C4, ignore anything else for now.
    $note = $note % 8;
    //save for later..
    //$voiceToTrigger = $this->noteTriggers($idx);
    //$this->voices[$voiceToTrigger]->trigger($vel);
    $this->voices[$note]->trigger($vel);
  }

  function noteOff($note, $vel) {
    //no action
  }

  function renderNextBlock() {
    //make stuff not done inside chunk
    $blockSize = $this->dspCore->rackRenderSize;
    //only do this if needed. 
    if (!$this->bufferEmpty) {
      $this->buffer = array_fill(0,$blockSize,0);
      $this->bufferEmpty = true;
    }
    //iterate over all voices and create a summed output.
    $voiceCount = sizeof($this->voices);
    for ($i=0; $i < $voiceCount; $i++) {
      $myVoice = &$this->voices[$i];
      if ($myVoice->checkVoiceActive()) {
        $myVoice->renderNextBlock($blockSize,$i);
        $this->bufferEmpty = false;
      }
    }
  }
}
