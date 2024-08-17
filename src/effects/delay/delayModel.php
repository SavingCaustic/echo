<?php

class DelayModel extends ParamsAbstract implements effectInterface {
    //simple delay acting more or less as an interface for writing effects
    var $rackRef;
    var $lfp;
    var $time;
    var $feedback;
    var $mix;
    var $fifo = array();
    var $fifoSize;    //sampleFreq * time
    var $fifoIdx;     //wr and rd same for now..
    var $fifoMax;
    var $params;

    function __construct($rack) {
        $this->rackRef = &$rack;
        //$this->lpf = new ResonantLowPassFilter(44100,100,2);
        //$this->lpf = new ButterLPFopt(44100,1000);
        $this->fifoSize = 48000; //0.5 sec max. Fixed array best for performance?
        $this->fifoIdx = 0;
        $this->fifoMax = 1000;
        $this->reset();
    }

    public function processClock() {
        //nothing
    }

    public function reset() {
        //imitate synth right..
        //should really be read from XML
        $this->fifo = array_fill(0,$this->fifoSize,0);

        $this->numParams = array(
            'FEEDBACK' => 0.1,
            'TIME' => 0.25,
            'MIX'=> 0.5
        );
        $this->pushAllParams();

        //save these default settings to be picked up by www-player
        file_put_contents(__DIR__ . '/defaults.json',json_encode($this->params));
    }

    function pushNumParam($name, $val) {
        switch($name) {
            case 'FEEDBACK':
                $this->feedback = $val;
                break;
            case 'MIX':
                $this->mix = $val;
                break;
            case 'TIME':
                $fifoReqSize = floor($val * TPH_SAMPLE_RATE * 1.1);
                if ($fifoReqSize > $this->fifoSize) $fifoReqSize = $this->fifoSize;
                $this->fifoMax = $fifoReqSize;    
                break;
        }
    }

    function pushStrParam($name, $val) {}

    function process(&$buffer) {        
        $bufferSize = TPH_RACK_RENDER_SIZE;
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
            $buffer[$i] = $sample;
            //$bufferOut[$i] = $sample;            
        }
        //return $bufferOut;
    }
}

