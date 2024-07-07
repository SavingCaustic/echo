<?php

class ADSHR {
    //Timing in this adsr is *linear*
    //if time is in mS, we're fine with int.
    //hmm.. This is a mix between synth and voice...
    var $voice;
    var $aFactor;
    var $dFactor;
    var $sLevel;
    var $hFactor;
    var $rFactor;
    var $state;
    var $srInv;
    var $level;
    var $mode;

    function __construct($voice, $sampleRate) {
        $this->voice = $voice;
        $this->srInv = 1000 / $sampleRate;
        $this->state = 'IDLE';
    }
    
    function setValues($a,$d,$s,$h,$r) {
        //time in sec, any ms needs to be 0.0020
        //actually, these *could* be induvidual per voice based on velocity or key-tracking..
        if ($a < 1) $a = 1; //what ? ms?
        if ($d < 1) $d = 1; //what ? ms?
        if ($h < 1) $h = 1; //what ? ms?
        if ($r < 1) $r = 1; //what ? ms?
        $this->aFactor = $this->srInv / $a; 
        $this->dFactor = $this->srInv / $d; 
        $this->sLevel = $s;
        $this->hFactor = $this->srInv / $h; 
        $this->rFactor = $this->srInv / $r; 
    }

    function getState() {
        //used by voice allocator.
        return $this->state;
    }
    
    function reset($hard = false) {
        if ($hard) $this->level = 0;
        $this->state = 'ATTACK';
    }

    function release() {
        $this->state = 'RELEASE';
    }

    function goIdle() {
        $this->voice->voiceDeactivate();
        $this->state = 'IDLE';
        $this->level = 0;
    }

    function getNextLevel($chunkSize) {
        switch ($this->state) {
            case 'IDLE':
                //do nothing
                if ($this->level != 0) $this->level = 0;
                break;
            case 'ATTACK':
                //ramp up. Only linear for now.
                $this->level += $this->aFactor * $chunkSize;
                if ($this->level >= 1) $this->state = 'DECAY';
                break;
            case 'DECAY':
                //ramp down. Only linear for now.
                $this->level -= $this->dFactor * $chunkSize;
                if ($this->level <= $this->sLevel) $this->state = 'SUSTAIN';
                break;
            case 'SUSTAIN':
                //stay and fade
                if ($this->level > $this->hFactor) {
                    $this->level -= $this->hFactor;
                } else {
                    $this->goIdle();
                }
                break;
            case 'RELEASE':
                if ($this->level > $this->rFactor) {
                    $this->level -= $this->rFactor * $chunkSize;
                } else {
                    $this->goIdle();
                }
                break;
        }
        return $this->level;
    }
}