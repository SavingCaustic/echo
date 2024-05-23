<?php
//require('beatnikFilter.php');

class BeatnikVoice {
    var $synthModel;
    var $waveform;
    var $active;
    var $note;
    var $velocilty;
    var $vca;
    var $vel;
    var $filter;

    function __construct($synthModel) {
        $this->synthModel = &$synthModel;
        $se = $this->synthModel->settings;
        $this->active = false;
    }

    function trigger($vel) {
        $this->vel = $vel;
        $this->samplePtr = 0;
        $this->active = true;
    }

    function setupSample($data) {
        //huh? Really in model. This shold be as fast as possible.
        $dataSize = sizeof($data);
        $pad = 256 - ($dataSize % 256) % 256;
        for($i=0;$i < $pad; $i++) {
            $data[] = 0;
        }
        $this->sample = $data;
        $this->samplePtr = 0;
        $this->sampleSize = sizeof($data);
        //die('asdf' . $this->sampleSize);
        //yeah, we need some static libraries for stuff we want to do. 
    }

    function checkVoiceActive() {
        return $this->active;
    }

    function voiceDeactivate() {
        //called by VCA release or hold complete
        $this->active = false;
    }

    function renderNextBlock($blockSize, $voiceIX, $blockCreated) {        
        // Read the data (samples)
        // actually, grab [blockSize] values and just copy back.
        // if we would add 256 zero bytes to the sample we would be safe to run the buffer.
        for ($i = 0; $i < $blockSize; $i++) {
            if ($this->samplePtr < $this->sampleSize) {
                $this->synthModel->buffer[$i] = $this->sample[$this->samplePtr] * $this->vel/127;
                $this->samplePtr++;
            } else {
                $this->synthModel->buffer[$i] = $this->sample[$this->samplePtr-1];
                $this->active = false;
            }    
            //$samples[$i] = 0;
        }
    }
 
}