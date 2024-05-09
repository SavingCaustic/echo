<?php
require('beatboxFilter.php');

class BeatboxVoice {
    var $synthModel;
    var $active;
    var $note;
    var $velocilty;
    var $vca;
    var $filter;

    function __construct($synthModel) {
        $this->synthModel = &$synthModel;
        $se = $this->synthModel->settings;
        $this->active = false;
        $this->vca = new AR($this, $this->synthModel->dspCore->sampleRate);
        $this->filter = new BeatboxFilter($this->synthModel->dspCore->sampleRate);      //maybe SubsynthFilter2 is cooler.. 
        $this->filter->setParams('LOWPASS', $se['VCF_CUTOFF'], $se['VCF_RESONANCE']);
    }

    function noteOn($note, $vel, $delay) {
        $this->note = $note;    //69 = A4
        //somehow make something out of velocity. What?
        $this->velocity = $vel; //1-127
        //$this->osc1->reset();
        $se = $this->synthModel->settings;
        $this->vca->setValues($se['VCA_ATTACK'], $se['VCA_DECAY'], $se['VCA_SUSTAIN'], $se['VCA_HOLD'], $se['VCA_RELEASE']);
        $this->vca->reset();
        //borrow VCA-values - let's not bother now..
        $this->vcf->setValues($se['VCF_ATTACK'], $se['VCF_DECAY'], $se['VCF_SUSTAIN'], $se['VCF_HOLD'], $se['VCF_RELEASE']);
        $this->vcf->reset();
        $this->active = true;   //will be picked up by next render.
    }

    function getVCAState() {
        return $this->vca->state;
    }

    function getVCALevel() {
        return $this->vca->level;
    }

    function vel2amp() {
        //converts velocity 0-127 to gain
        $gain = 1 + ($this->velocity - 64)/64;
        return $gain;
    }

    function checkVoiceActive() {
        return $this->active;
    }

    function voiceDeactivate() {
        //called by VCA release or hold complete
        $this->active = false;
    }

    function noteOff($delay) {
        //remove note-ref so not double fired.
        //no, on contrary - keep and replace if re-fired
        $this->vca->release();
    }

    function renderNextBlock($blockSize, $voiceIX, $blockCreated) {        
        //for efficiency, some calculations are only done on intervals.
        $se = $this->synthModel->settings;
        $chunkSize = 64;
        for($i=0;$i<$blockSize;$i+=$chunkSize) {
            //do filter calculations, to spare cpu, not every sample.
            $filterMod = $this->vcf->getNextLevel($chunkSize);
            $this->filter->calcCoefficients($filterMod * $se['VCF_CUTOFF']);
            $pitchMod = 0;  //same..
            $pitchMod = $this->synthModel->lfo1Sample;

            $osc2_hz = $this->synthModel->dspCore->noteToHz($this->note + $pitchMod + $this->synthModel->osc2_noteOffset); 
            $osc1_hz = $this->synthModel->dspCore->noteToHz($this->note + $pitchMod); //$this->note * $fm
            //check envelope target here instead.
            //die('osc2:' . $osc2_hz);
            for($j=0;$j<$chunkSize;$j++) {
                $ampMod = $this->vca->getNextLevel(1);  //no chunk on vca.
                $ampMod *= $this->vel2amp();
                //osc2 first since it may modulate osc1.
                $osc1_sample = $this->osc1->getNextSample($osc1_hz, 0); //$fm); //, $phase);
                
                $sample = $osc1_sample * (1 - $this->synthModel->osc_mix) + $osc2_sample * ($this->synthModel->osc_mix); 
                //feed the sample into the VCF somehow..
                $sample = $this->filter->applyFilter($sample);
                //
                $sample *= $ampMod; //introduces noise * (0.5 + sqrt(abs($pitchMod*0.1)));
                if($blockCreated) { //block created from synthRender-method
                    //add
                    $this->synthModel->buffer[$i+$j] += $sample;
                } else {
                    //init
                    $this->synthModel->buffer[$i+$j] = $sample;
                }
            }
            //store the last sample as delta for ramp on retrigger.
            $this->lastVal = $sample;
        }
    }

 
}