<?php

class DelayModel {
    //simple delay acting more or less as an interface for writing effects
    var $lfp;       //object for a filter - tape effect.. can we really motivate that?
    var $time;
    var $feedback;
    var $mix;
    var $fifo;
    var $fifoSize;    //sampleFreq * time
    var $fifoIdx;     //wr and rd same for now..
    var $fifoMax;

    function __construct($dspCore) {
        $this->dspCore = &$dspCore;
        //$this->lpf = new ResonantLowPassFilter(44100,100,2);
        //$this->lpf = new ButterLPFopt(44100,1000);
        $this->fifoSize = 22050; //0.5 sec max. Fixed array best for performance?
        $this->fifoIdx = 0;
        $this->fifoMax = 1000;
        $this->initSettings(); //dunno..
        $this->pushSettings();
        //$this->setValues(165,0.3,0.3);
        //in C, we need to setup size of array somehow, and we need to zero it!
        for($i=0;$i<$this->fifoSize;$i++) $this->fifo[$i] = 0;
    }

    function initSettings() {
        //imitate synth right..
        $this->settings = array(
            'FEEDBACK' => 0.3,
            'TIME' => 150,
            'MIX'=> 0.4
        );
        //save these default settings to be picked up by www-player
        file_put_contents(__DIR__ . '/defaults.json',json_encode($this->settings));
    }

    function pushSettings() {
        //experimental function that pushes settings to non-readable, optimized registers.
        $se = $this->settings;
        $this->feedback = $se['FEEDBACK'];
        $this->mix = $se['MIX'];
        //
        $fifoReqSize = floor($se['TIME'] * $this->dspCore->sampleRate * 0.001);
        if ($fifoReqSize > $this->fifoSize) $fifoReqSize = $this->fifoSize;
        $this->fifoMax = $fifoReqSize;    
    }

    function process($buffer) {        
        $bufferSize = $this->dspCore->rackRenderSize;
        $bufferOut = array();
        for($i=0;$i<$bufferSize;$i++) {
            $echo = $this->fifo[$this->fifoIdx];
            //$this->lpf->setCutoffFrequency(200);
            //$echo = $this->lpf->filter($echo);  //meebe 
            //$echo = $this->lpf->filter($echo) * 0.5;
            //push value from buffer to ring-buffer, possibly with some feedback
            $feedback = $echo * $this->feedback;
            //$feedback = $this->lpf->filter($feedback);
            $this->fifo[$this->fifoIdx] = $buffer[$i] + $feedback;
            if ($this->fifoIdx > $this->fifoMax) {
                $this->fifoIdx = 0;
            } else {
                $this->fifoIdx++;
            }
            //$this->fifoIdx++;
            //$this->fifoIdx %= floor(44.1 * $this->time);
            //throw back the signal to the buffer.
            //mix = 0 = dry, 1 = wet
            $sample = $echo * $this->mix + $buffer[$i] * (1-$this->mix);
            $bufferOut[$i] = $sample;            
        }
        return $bufferOut;
    }
}

