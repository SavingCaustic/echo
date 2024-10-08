<?php
require('subrealFilter.php');

class SubrealVoice {
    var $synthModel;
    var $active;
    var $note;
    var $velocilty;
    var $osc1;
    var $osc2;
    var $lfo1AR;
    var $vcf;
    var $vca;
    var $filter;

    function __construct($synthModel) {
        $this->synthModel = &$synthModel;
        $strSettings = $this->synthModel->getAllStrParams();
        $numSettings = $this->synthModel->getAllNumParams();
        $this->active = false;
        //$this->lfo1 = new LFO($this->synthModel->dspCore);   //ok, this is the shared-lfo, not voice-lfo.
        $this->lfo1AR = new AR($this->synthModel->dspCore);
        $this->osc1 = new CoreOscillator($this->synthModel->dspCore);
        $this->osc1->setWaveform($strSettings['OSC1_WF']);
        $this->osc2 = new CoreOscillator($this->synthModel->dspCore);
        $this->osc2->setWaveform($strSettings['OSC2_WF']);
        $this->vcf = new ADSHR($this);
        $this->vca = new ADSHR($this);
        $this->filter = new SubrealFilter();      //maybe SubsynthFilter2 is cooler.. 
        $this->filter->setParams('LOWPASS', $numSettings['VCF_CUTOFF'], $numSettings['VCF_RESONANCE']);
    }

    function noteOn($note, $vel) {
        //ADSHR-values should be picked from synth and calculated based on velocity etc.
        //setup the note and then let renderNextBlock do the work
        $this->note = $note;    //69 = A4
        //somehow make something out of velocity. What?
        $this->velocity = $vel; //1-127
        //$this->osc1->reset();
        $se = $this->synthModel->getAllNumParams();
        $this->lfo1AR->reset();
        $this->lfo1AR->setAR($se['LFO1_RAMP'], 0, 'linear', 256);
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
        //if ($this->note == 80) die('now');
        //$this->active = false;
    }

    function noteOff() {
        //remove note-ref so not double fired.
        //no, on contrary - keep and replace if re-fired
        $this->vca->release();
        $this->vcf->release();
    }

    function renderNextBlock($blockSize, $voiceIX) {        
        //for efficiency, some calculations are only done on intervals.
        $se = $this->synthModel->getAllNumParams();
        $seStr = $this->synthModel->getAllStrParams();
        $chunkSize = 64;
        $nextLevel = $this->lfo1AR->getNextLevel($blockSize);
        for($i=0;$i<$blockSize;$i+=$chunkSize) {
            //maybe we should have a copy of the setting, right?
            //do filter calculations, to spare cpu, not every sample.
            $filterMod = $this->vcf->getNextLevel($chunkSize);
            $filterFreq = $se['VCF_CUTOFF'] * 0.2 + 0.8 * $se['VCF_CUTOFF'] * $filterMod;
            $filterRes = $se['VCF_RESONANCE'] * $filterMod;
            $this->filter->calcCoefficients($filterFreq, $filterRes);
            //$filterMod * $se['VCF_CUTOFF']);
            //LFO1
            $lfoHz = $se['LFO1_SPEED'];
            $lfoAmp = $se['LFO1_DEPTH'];
            $pmOsc1 = 0;
            if ($seStr['LFO1_TARGET'] == 'OSC1') $pmOsc1 = $this->synthModel->lfo1Sample * $nextLevel * $lfoAmp;
            $pmOsc2 = 0 * $pmOsc1;
            //$pmOsc1 = 0;
            //some memory here?
            $osc2_hz = $this->synthModel->dspCore->noteToHz($this->note + $pmOsc2 + $this->synthModel->osc2_noteOffset); 
            $osc1_hz = $this->synthModel->dspCore->noteToHz($this->note + $pmOsc1); //$this->note * $fm
            //check envelope target here instead.
            //die('osc2:' . $osc2_hz);
            for($j=0;$j<$chunkSize;$j++) {
                $ampMod = $this->vca->getNextLevel(1);  //no chunk on vca.
                $ampMod *= $this->vel2amp();
                //osc2 first since it may modulate osc1.
                $osc2_sample = $this->osc2->getNextSample($osc2_hz);
                //modulate osc1.. fm, pm = before, am = after
                $fm = 0;
                //DEFAULT
                switch($this->synthModel->osc2_modType) {
                    case 'FM':
                        $osc1_sample = $this->osc1->getNextSample($osc1_hz, $osc2_sample * $this->synthModel->osc2_modLevel);
                        break;
                    case 'AM':
                        $osc1_sample = $this->osc1->getNextSample($osc1_hz);
                        $osc1_sample = (
                            ($osc1_sample * (1 - $this->synthModel->osc2_modLevel)) + 
                            ($osc1_sample * $this->synthModel->osc2_modLevel) * $osc2_sample);
                        break;
                    case 'PM':
                        //just make something up?
                        break;                    
                    default:
                        $osc1_sample = $this->osc1->getNextSample($osc1_hz, 0); //$fm); //, $phase);
                }
                $sample = $osc1_sample * (1 - $this->synthModel->osc_mix) + $osc2_sample * ($this->synthModel->osc_mix); 
                //feed the sample into the VCF somehow..
                $sample = $this->filter->applyFilter($sample) * $ampMod;
                //
                //$sample *= $ampMod; //introduces noise * (0.5 + sqrt(abs($pitchMod*0.1)));
                $this->synthModel->buffer[$i+$j] += $sample * 0.5; //0.5 = really 1/voicecount..
            }
        }
    }

 
}