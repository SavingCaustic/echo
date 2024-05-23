<?php

class DelayModel implements effectInterface {
    //simple delay acting more or less as an interface for writing effects
    var $lfp;
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
        $this->reset(); //dunno..
        $this->pushParams();
        //$this->setValues(165,0.3,0.3);
        //in C, we need to setup size of array somehow, and we need to zero it!
        for($i=0;$i<$this->fifoSize;$i++) $this->fifo[$i] = 0;
    }

    function reset() {
        //imitate synth right..
        //should really be read from XML
        $this->params = array(
            'FEEDBACK' => 0.2,
            'TIME' => 0.2,
            'MIX'=> 0.5
        );
        //save these default settings to be picked up by www-player
        file_put_contents(__DIR__ . '/defaults.json',json_encode($this->params));
    }

    function setParam($name, $val, $push = true) {
        if (!array_key_exists($name, $this->params)) die('bad setting ' . $name);
        $this->params[$name] = $val;
        if ($push) $this->pushParam($name, $val);
    }

    function pushParams() {
        //experimental function that pushes settings to non-readable, optimized registers.
        foreach($this->params as $key=>$val) {
            $this->pushParam($key, $val);
        }
    }

    function pushParam($name, $val) {
        //experimental function that pushes settings to non-readable, optimized registers.
        $se = $this->params;
        $this->feedback = $se['FEEDBACK'];
        $this->mix = $se['MIX'];
        //
        $fifoReqSize = floor($se['TIME'] * $this->dspCore->sampleRate * 1);
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

