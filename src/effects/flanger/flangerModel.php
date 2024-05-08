<?php
//o my, chatGTP wrote this when requesting a phaser. Needs QA.

class FlangerModel {
    var $dspCore;
    var $sampleRate;
    var $delayMax;
    private $depth;
    private $rate;
    private $feedback;
    private $wet;
    var $delayLine[];

    function __construct($dspCore) {
        $this->dspCore = &$dspCore;
        $this->sampleRate = $this->dspCore->sampleRate;
        $this->delayMax = 0.01; // Maximum delay in seconds (adjust as needed)
        $this->depth = 0.5;
        $this->rate = 0.5;
        $this->feedback = 0.7;
        $this->wet = 0.5;
        $this->delayLine = array_fill(0, $this->delayMax * $sampleRate, 0);
        $this->phase = 0;
    }

    function setValues($depth, $rate, $feedback, $wet) {
        $this->depth = $depth;
        $this->rate = $wet;
        $this->feedback = $feedback;
        $this->wet = $wet;
    }

    public function process($buffer) {
        $bufferSize = $this->dspCore->rackRenderSize;
        $bufferOut = array();
        $delay = $this->delayMax * $this->depth; // Adjusted delay based on depth parameter
        $rate = $this->rate; // LFO rate
        $feedback = $this->feedback; // Feedback amount
        $wet = $this->wet; // Wet/dry mix
        for($i=0;$i<$bufferSize;$i++) {
            // All-pass filter
            $delayedSample = array_shift($delayLine);
            $delayLine[] = $sample + $feedback * $delayedSample;

            // Modulate delay time using an LFO
            $phase += $rate / $sampleRate;
            if ($phase >= 1) {
                $phase -= 1;
            }
            $modulatedDelay = $delay * (1 + sin(2 * M_PI * $phase));

            // Mix dry and wet signals with modulated delay
            $bufferOut[$i] = (1 - $wet) * $sample + $wet * $delayLine[round($modulatedDelay * $sampleRate)];
        }
        return $bufferOut;
    }

}


?>
