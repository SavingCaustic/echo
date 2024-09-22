<?php

//this should use getSetters 
class RackEmitter {
    private $sampleRate;
    private $lowFreq;
    private $midFreq;
    private $highFreq;
    private $lowGain;
    private $midGain;
    private $highGain;

    private $filterStateBuffer;  // Separate filter state buffer for each band and channel

    //+/- 10dB seems ok.
    public function __construct($sampleRate = 48000, $lowGain = 0, $midGain = 0, $highGain = 6) {
        $this->sampleRate = $sampleRate;
        $this->lowFreq = 80;
        $this->midFreq = 1500;
        $this->highFreq = 8000;
        $this->setGains($lowGain, $midGain, $highGain);

        $this->reset();
    }

    public function reset() {
        // Initialize a filter state buffer for all bands and channels
        // Structure: [band][channel][state], total size = 3 (bands) * 2 (channels) * 4 (states) = 24 elements
        // Each filter requires 4 states: x1, x2 (previous inputs) and y1, y2 (previous outputs)
        $this->filterStateBuffer = array_fill(0, 24, 0.0);        
    }

    public function setGains($lowGain, $midGain, $highGain) {
        $this->lowGain = $this->decibelToLinear($lowGain);
        $this->midGain = $this->decibelToLinear($midGain);
        $this->highGain = $this->decibelToLinear($highGain);
    }

    private function decibelToLinear($db) {
        return pow(10, $db / 20);
    }

    public function process(&$audioBuffer, $isStereo = false) {
        $this->processEQ($audioBuffer, $isStereo);
        $this->processPanAndFader($audioBuffer, $isStereo);
    }

    public function processPanAndFader(&$audioBuffer, $isStereo) {
        $pan = -0.1;
        $faderGain = 0.3 ;//db2gain earlier..
        $leftGain = $this->panGain($pan) * $faderGain;
        $rightGain = $this->panGain($pan, true) * $faderGain;
        $rightOffset = $isStereo ? 1 : 0;
        for ($i = 0; $i < TPH_RACK_RENDER_SIZE * 2; $i = $i + 2) {
            $audioBuffer[$i] = $audioBuffer[$i] * $leftGain;
            $audioBuffer[$i+1] = $audioBuffer[$i+$rightOffset] * $rightGain;
        }
    }

    function panGain($pan, $right = false) {
        if ($right) {
            //0 - 1
            //gain_right = std::sin((M_PI / 2) * ((pan + 1) / 2.0f));
            $gain = sin((M_PI / 2) * ($pan + 1) / 2);
        } else {
            //-1 - 0
            //gain_left = std::cos((M_PI / 2) * ((pan + 1) / 2.0f));
            $gain = cos((M_PI / 2) * ($pan + 1) / 2);
        }
        return $gain;
    }

    public function processEQ(&$audioBuffer, $isStereo) {
        if ($this->lowGain != 1.0) {
            $this->applyBand($audioBuffer, 0, $this->lowFreq, $this->lowGain, 0);
            if ($isStereo) $this->applyBand($audioBuffer, 1, $this->lowFreq, $this->lowGain, 1);
        }

        if ($this->midGain != 1.0) {
            $this->applyBand($audioBuffer, 0, $this->midFreq, $this->midGain, 2);
            if ($isStereo) $this->applyBand($audioBuffer, 1, $this->midFreq, $this->midGain, 3);
        }

        if ($this->highGain != 1.0) {
            $this->applyBand($audioBuffer, 0, $this->highFreq, $this->highGain, 4);
            if ($isStereo) $this->applyBand($audioBuffer, 1, $this->highFreq, $this->highGain, 5);
        }
    }

    private function applyBand(&$audioBuffer, $channel, $frequency, $gain, $bandOffset) {
        // Calculate filter coefficients (biquad formula)
        $omega = 2 * pi() * $frequency / $this->sampleRate;
        $cosOmega = cos($omega);
        $q = 0.4;
        $alpha = sin($omega) / (2 * $q); // Assuming a Q factor of 1/sqrt(2) for simplicity

        $a0 = 1 + $alpha;
        $a1 = -2 * $cosOmega;
        $a2 = 1 - $alpha;
        $b0 = (1 + $alpha * $gain);
        $b1 = $a1;
        $b2 = (1 - $alpha * $gain);
        if (abs($a0) < 0.01) return;
        // Normalize coefficients by a0
        $b0 /= $a0;
        $b1 /= $a0;
        $b2 /= $a0;
        $a1 /= $a0;
        $a2 /= $a0;

        // Offset calculation for the filter state buffer
        $offset = $bandOffset * 8;  // Calculate the base offset for the band (8 elements per band: 4 for L, 4 for R)

        // Filter state variables for the left or right channel
        $x1 = &$this->filterStateBuffer[$offset + $channel * 4];      // Previous input (x1)
        $x2 = &$this->filterStateBuffer[$offset + $channel * 4 + 1];  // Previous input (x2)
        $y1 = &$this->filterStateBuffer[$offset + $channel * 4 + 2];  // Previous output (y1)
        $y2 = &$this->filterStateBuffer[$offset + $channel * 4 + 3];  // Previous output (y2)

        // Apply the filter to each sample in the audio buffer
        for ($i = $channel; $i < TPH_RACK_RENDER_SIZE * 2; $i += 2) {
            $sample = $audioBuffer[$i];

            // Apply the biquad filter equation
            $yNew = $b0 * $sample + $b1 * $x1 + $b2 * $x2 - $a1 * $y1 - $a2 * $y2;

            // Update filter state for the next sample
            $x2 = $x1;
            $x1 = $sample;
            $y2 = $y1;
            $y1 = $yNew;

            // Store the filtered sample back into the audio buffer
            $audioBuffer[$i] = $yNew;
        }
    }
}
