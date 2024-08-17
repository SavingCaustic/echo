<?php
require('subrealVoice.php');

//This is *not* the controller. It needs to be run in the audio-thread.
//settings are not midi-based but optimized for distribution to units.

class SubrealModel extends ParamsAbstract implements SynthInterface {
    //objects
    var $dspCore;
    var $lfo1;
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
        $this->debug = false;
        $this->initSettings();
        //needs settings above..
        $this->setupVoices(4);
        $this->reset();
    }

    public function reset() {
        $this->initSettings();
        $this->pushAllParams();
    }

    function initSettings() {
        $this->loadDefaultParams(__DIR__ . '/defaults.json');
    }

    public function pushNumParam($name, $val) {
        switch ($name) {
            case 'LFO1_ATTACK':
                //just hold this and copy to voice when triggering one.
                //
                break;
            case 'OSC2_OCT':
            case 'OSC2_SEMITONES':
                //look in settings
                $this->osc2_noteOffset = $this->getNum('OSC2_OCT') * 12 + $this->getNum('OSC2_SEMITONES');
                break;
            case 'OSC2_MODLEVEL':
                $this->osc2_modLevel = $val;
                break;
            case 'OSC_MIX':
                $this->osc_mix = $val;
                break;
        }
    }

    public function pushStrParam($name, $val) {
        switch ($name) {
            case 'LFO1_WF':
                $this->lfo1->setWaveform($val);
                break;
            case 'OSC2_MODTYPE':
                $this->osc2_modType = $val;
                break;
        }
    }

    function setupVoices($voiceCnt) {
        //called on init or polyphony change
        //in C, maybe GC existing voice-objects?
        $this->polyphony = $voiceCnt;
        for ($i = 0; $i < $voiceCnt; $i++) {
            //voice grabs the settings it needs
            $this->voices[$i] = new SubrealVoice($this);
        }
    }

    public function parseMidi($cmd, $param1 = null, $param2 = null) {
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
        foreach ($this->voices as $voice) {
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
        for ($i = 0; $i < $this->polyphony; $i++) {
            $myVoice = &$this->voices[$i];
            if ($myVoice->note == $note) {
                //re-use (what about lfo-ramp here..)
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
        $blockSize = TPH_RACK_RENDER_SIZE;
        //LFO1
        $se = $this->settings;
        $lfoHz = $this->getNum('LFO1_RATE');
        $this->lfo1Sample = $this->lfo1->getNextSample($blockSize * $lfoHz);
        //iterate over all voices and create a summed output.
        $voiceCount = sizeof($this->voices);
        $blockCreated = false;
        $this->buffer = array_fill(0, $blockSize, 0);
        for ($i = 0; $i < $voiceCount; $i++) {
            $myVoice = &$this->voices[$i];
            if ($myVoice->checkVoiceActive()) {
                $myVoice->renderNextBlock($blockSize, $i); //if i == 0, init buffer, else +=
                $blockCreated = true;
            }
        }
        if ($blockCreated) {
            //check for any analog distorsion fix
            $distLevel = 2.5; //dunno really what to make of this. Should i have voice-amp based on voices?
            $distFactor = 1.4;
            $distFactorNeg = 2.2;
            for ($i = 0; $i < $blockSize; $i++) {
                if ($this->buffer[$i] > $distLevel) {
                    //multiplication-factor lowering as we go over 0.9
                    $factor = pow(($distLevel / $this->buffer[$i]), $distFactor);
                    $this->buffer[$i] = $distLevel + ($this->buffer[$i] - $distLevel) * $factor;
                } elseif ($this->buffer[$i] < $distLevel * -1) {
                    $factor = pow(abs($distLevel / $this->buffer[$i]), $distFactorNeg);
                    $this->buffer[$i] = 0 - $distLevel + ($this->buffer[$i] + $distLevel) * $factor;
                }
            }
        }
    }
}
