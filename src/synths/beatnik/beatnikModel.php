<?php
require 'beatnikVoice.php';
require __DIR__ . '/../../utils/wavReader.php';

//a 8-channel sample player. We add filters and stuff later. minimal now.
//voices could be static, no need for classes really.

class BeatnikModel extends ParamsAbstract implements SynthInterface {
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
    //this is good for now. Maybe later we have an xml-def with loops and stuff but until then:
    //keys: CDEFGAH
    //maybe adjcent keys for pitch adjustments, so 8x3=24
    //HCC# = sample1
    $default = array(
      'lm-2/kick.wav',
      'lm-2/snare-m.wav',
      'lm-2/hihat-closed.wav',
      'lm-2/hihat-open.wav',
      'lm-2/tom-hh.wav',
      'lm-2/tom-h.wav',
      'lm-2/tom-m.wav',
      'lm-2/conga-h.wav'
      //'lm-2/tom-l.wav',
    );
    $WR = new WavReader();

    for($i=0;$i<8;$i++) {
      $data = $WR->wav2buffer($this->dspCore->appDir . '/assets/synths/beatnik/samples/' . $default[$i]);
      $this->voices[$i]->setupSample($data);
    }
  } 

  function pushNumParam($name, $val) {}

  function pushStrParam($name, $val) {}

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
    $blockSize = TPH_RACK_RENDER_SIZE;
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
