<?php
require('subrealVoice.php');

//This is *not* the controller. It needs to be run in the audio-thread.
//settings are not midi-based but optimized for distribution to units.

class SubrealModel implements SynthInterface {
  //objects
  var $dspCore;
  var $lfo1;
  var $lfo1AR;
  var $voices;
  //variables
  var $polyphony;
  var $nextBlock;
  var $buffer;
  var $debug;
  var $settings;
  //private shared (non-voice) registers
  var $osc1_wf;
  var $osc2_wf;
  var $osc2_noteOffset;
  var $osc2_modType;
  var $osc2_modLevel;
  var $osc_mix;
  
  function __construct($dspCore) {
    $this->dspCore = &$dspCore;
    $this->lfo1 = new LFO($this->dspCore);   //ok, this is the shared-lfo, not voice-lfo.
    $this->lfo1AR = new AR($this->dspCore);
    $this->debug = false;
    $this->initSettings();
    $this->setupVoices(4);
    $this->pushSettings();
  }

  function reset() {
    $this->initSettings();
    $this->pushSettings();
  }

  function initSettings() {
    //parameters to be picked up at run-time.
    //validation, enumeration etc in parameters.json
    //akward fix
    if(isset($_SESSION['OSC1_WF'])) {
      $this->settings = $_SESSION;
      return;
    }
    $this->settings = array(
      'OSC1_WF' => 'sine',
      'OSC2_WF' => 'sine',
      'OSC2_OCT' => 2,
      'OSC2_SEMITONES' => 0,
      'OSC2_MODTYPE' => 'AM',
      'OSC2_MODLEVEL' => 0.2,
      'OSC_MIX' => 0.2, 
      'VCA_ATTACK' => 5,
      'VCA_DECAY' => 10,
      'VCA_SUSTAIN' => 0.8,
      'VCA_HOLD' => 3000,
      'VCA_RELEASE' => 1000,
      'VCF_ATTACK' => 5,
      'VCF_DECAY' => 10,
      'VCF_SUSTAIN' => 0.8,
      'VCF_HOLD' => 3000,
      'VCF_RELEASE' => 1000,
      'LFO1_WF' => 'sine',
      'LFO1_DEPTH' => 0,
      'LFO1_RATE' => 5, 
      'LFO1_ATTACK' => 1000,
      'VCF_CUTOFF' => 1000,
      'VCF_RESONANCE' => 1,
    );
    //save these default settings to be picked up by www-player
    file_put_contents(__DIR__ . '/defaults.json',json_encode($this->settings));
  }

  function pushSettings() {
    //iterate over all settings and set them to respective (private) register.
    foreach($this->settings as $key=>$val) {
      $this->pushSetting($key);
    }
  }

  function pushSetting($setting) {
    //oh this will be long..
    $se = $this->settings;
    $val = $se[$setting];
    //val maybe *always* string?
    switch($setting) {
      case 'LFO1_WF':
        $this->lfo1->setWaveform($val);
        break;
      case 'LFO1_ATTACK':
        $this->lfo1AR->setAR($val, 0, 'linear', 256);
        break;
      case 'OSC2_OCT':
      case 'OSC2_SEMITONES':
        //look in settings
        $this->osc2_noteOffset = $se['OSC2_OCT'] * 12 + $se['OSC2_SEMITONES'];
        break;
      case 'OSC2_MODTYPE':
        $this->osc2_modType = $val;
        break;
      case 'OSC2_MODLEVEL':
        $this->osc2_modLevel = $val;
        break;
      case 'OSC_MIX':
        $this->osc_mix = $val;
        break;
    }
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
    $this->polyphony = $voiceCnt;
    for($i=0; $i < $voiceCnt; $i++) {
      //voice grabs the settings it needs
      $this->voices[$i] = new SubrealVoice($this);
    }
  }

  function parseMidi($cmd, $param1, $param2) {
    $cmdMSN = $cmd & 0xf0;
    if ($cmdMSN == 0x90) $this->noteOn($param1, $param2);
    if ($cmdMSN == 0x80) $this->noteOff($param1, 0);
  }

  function noteOn($note, $vel) {
    $voiceNo = $this->findVoiceToAllocate($note);
    if ($voiceNo != -1) {
      //should calculation of attack (based of velocity) be done here?
      $this->voices[$voiceNo]->noteOn($note, $vel);
    }
  }

  function noteOff($note, $vel) {
    //scan all voices for matching note, if no match, ignore
    foreach($this->voices as $voice) {
      if ($voice->note == $note) {
        $voice->noteOff();
      }
    }
  }

  function findVoiceToAllocate($note) {
    /* search for:
      1) Same note - re-use voice
      2) Idle voice
      3) Released voice - find most silent.
      4) Give up - return -1
    */
    $targetVoice = -1;
    $sameVoice = -1;
    $idleVoice = -1;
    $releasedVoice = -1;
    $releasedVoiceAmp = 1;
    //
    for($i=0;$i < $this->polyphony; $i++) {
      $myVoice = &$this->voices[$i];
      if ($myVoice->note == $note) {
        //re-use
        $sameVoice = $i;
        break;
      }
      if ($idleVoice == -1) {
        //not found yet so keep looking
        if ($myVoice->getVCAstate() == 'IDLE') $idleVoice = $i;
      }
      if ($myVoice->getVCAstate() == 'RELEASE') {
        //candidate, see if amp lower than current.
        $temp = $myVoice->getVCALevel();
        if ($temp < $releasedVoiceAmp) {
          //candidate!
          $releasedVoice = $i;
          $releasedVoiceAmp = $temp; 
        }
      }
    }
    $targetVoice = ($sameVoice != -1) ? $sameVoice : (
      ($idleVoice != -1) ? $idleVoice : (
        ($releasedVoice != -1) ? $releasedVoice : (-1)));
    //if ($this->debug) 
    //echo 'allocated voice: ' . $targetVoice . ' for note ' . $note . "\r\n";
    return $targetVoice;
  }

  function renderNextBlock() {
    //make stuff not done inside chunk
    $blockSize = $this->dspCore->rackRenderSize;
    //LFO1
    $se = $this->settings;
    $lfoHz = $se['LFO1_RATE'];
    $lfoAmp = $se['LFO1_DEPTH'];
    $this->lfo1Sample = $this->lfo1->getNextSample($blockSize * $lfoHz) *
                        $this->lfo1AR->getNextLevel($blockSize) * $lfoAmp; 
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
    } else {
      //check for any analog distorsion fix
      $distLevel = 1.5;
      $distFactor = 1.4;
      $distFactorNeg = 2.2;
      for($i=0;$i<$blockSize;$i++) {
        if ($this->buffer[$i]>$distLevel) {
          //multiplication-factor lowering as we go over 0.9
          $factor = pow(($distLevel / $this->buffer[$i]),$distFactor);
          $this->buffer[$i] = $distLevel + ($this->buffer[$i] - $distLevel) * $factor;
        } elseif ($this->buffer[$i] < $distLevel*-1) {
          $factor = pow(abs($distLevel / $this->buffer[$i]),$distFactorNeg);
          $this->buffer[$i] = 0 - $distLevel + ($this->buffer[$i] + $distLevel) * $factor;
        }
      }
    }
  }
}
