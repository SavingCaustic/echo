<?php
require('beatnikFilter.php');

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
        $this->filter = new BeatnikFilter(44100);
        $se = $this->synthModel->settings;
        $this->active = false;
    }

    function trigger($vel) {
        $this->vel = $vel;
        $this->samplePtr = 0;
        $this->active = true;
        $this->filter->setParams('LOWPASS', $vel * 100+5000, 1); // - $vel / 150);
    }

    function setupSample($data) {
        $dataSize = sizeof($data);
        $pad = 256 - ($dataSize % 256) % 256;
        for($i=0;$i < $pad; $i++) {
            $data[] = 0;
        }
        $this->sample = $data;
        $this->samplePtr = 0;
        $this->sampleSize = sizeof($data);
    }

    function checkVoiceActive() {
        return $this->active;
    }

    function voiceDeactivate() {
        //called by VCA release or hold complete
        $this->active = false;
    }

    function renderNextBlock($blockSize, $voiceIX) {        
        for ($i = 0; $i < $blockSize; $i++) {
            if ($this->samplePtr < $this->sampleSize) {
                $this->synthModel->buffer[$i] += //($this->sample[$this->samplePtr++]) * $this->vel/127; 
                    $this->filter->applyFilter($this->sample[$this->samplePtr++]) * $this->vel/127;
            } else {
                $this->synthModel->buffer[$i] += 0;
                $this->active = false;
            }    
        }
    }
 
}