<?php
require('beatboxVoice.php');

//ok, a variant of subsynth voices, but one voice for each sample.
//even if samples cancel out each outer (groups) they may have different pitch etc.

class BeatboxModel implements SynthInterface {
  //objects
  var $dspCore;
  var $voices;
  //variables
  var $polyphony;
  var $nextBlock;
  var $buffer;
  var $debug;
  var $settings;
  //private shared (non-voice) registers
  
  function __construct($dspCore) {
    $this->dspCore = &$dspCore;
    $this->debug = false;
    $this->initSettings();
    $this->setupVoices(8);
    $this->loadDefaults();
    $this->pushSettings();
  }

  function initSettings() {
    //parameters to be picked up at run-time.
    //validation, enumeration etc in parameters.json
    //akward fix
    $noteTriggers = array(0,0,1,1,2,3,3,4,4,5,5,6,7);
  }
  
  function pushSettings() {
    //iterate over all settings and set them to respective (private) register.
    foreach($this->settings as $key=>$val) {
      $this->pushSetting($key);
    }
  }

  function pushSetting($setting) {
  }

  public function setParam($name,$val) {
    //used by test-scripts so keep..
    if (!array_key_exists($name, $this->settings)) die('bad setting ' . $name);
    $this->settings[$name] = $val;
    $this->pushSetting($name);
  }

  function setupVoices($voiceCnt) {
    //called on init or polyphony change
    //in C, maybe GC existing voice-objects?
    //voices shared (like OH/CH have *different voices* since different AR, pitch etc.
    //voices has ramp to avoid clipping
    $this->polyphony = $voiceCnt;
    for($i=0; $i < $voiceCnt; $i++) {
      //voice grabs the settings it needs
      $this->voices[$i] = new beatboxVoice($this);
    }
  }

  function noteOn($note, $vel) {
    //triggers fixed to C3-C4, ignore anything else for now.
    if ($note < 48 || $note > 60) return;
    //not sure if we should settle for 8 samples but good for edit-grid too.
    $idx = $note - 48;
    $voiceToTrigger = $this->noteTriggers($idx);
    $this->voices[$voiceToTrigger]->trigger($vel);
  }


  function noteOff($note, $vel) {
    //no ation
  }

  function renderNextBlock() {
    //make stuff not done inside chunk
    $blockSize = $this->dspCore->rackRenderSize;
    //iterate over all voices and create a summed output.
    $voiceCount = sizeof($this->voices);
    $blockCreated = false;
    for ($i=0; $i < $voiceCount; $i++) {
      $myVoice = &$this->voices[$i];
      if ($myVoice->checkVoiceActive()) {
        $myVoice->renderNextBlock($blockSize,$i,$blockCreated); //if i == 0, init buffer, else +=
        $blockCreated = true;
      }
    }
    if (!$blockCreated) {
      //no voices has created the buffer, we need to create a silent one, or should this be done by rack?
      $this->buffer = array_fill(0,$blockSize,0);
    }
  }
}
