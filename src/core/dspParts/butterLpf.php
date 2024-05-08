<?php

class ResonantLowPassFilter {
    private $samplingFrequency;
    private $cutoffFrequency;
    private $resonance;
    private $x1 = 0.0;
    private $x2 = 0.0;
    private $y1 = 0.0;
    private $y2 = 0.0;

    public function __construct($samplingFrequency, $cutoffFrequency, $resonance) {
        $this->samplingFrequency = $samplingFrequency;
        $this->setCutoffFrequency($cutoffFrequency);
        $this->setResonance($resonance);
    }

    public function setCutoffFrequency($cutoffFrequency) {
        $this->cutoffFrequency = $cutoffFrequency;
    }

    public function setResonance($resonance) {
        $this->resonance = $resonance;
    }

    public function filter($input) {
        $omega = 2 * pi() * $this->cutoffFrequency / $this->samplingFrequency;
        $alpha = sin($omega) / (2 * $this->resonance);

        $b0 = (1 - cos($omega)) / 2;
        $b1 = 1 - cos($omega);
        $b2 = (1 - cos($omega)) / 2;
        $a0 = 1 + $alpha;
        $a1 = -2 * cos($omega);
        $a2 = 1 - $alpha;

        $output = ($b0 / $a0) * $input + ($b1 / $a0) * $this->x1 + ($b2 / $a0) * $this->x2
                - ($a1 / $a0) * $this->y1 - ($a2 / $a0) * $this->y2;

        $this->x2 = $this->x1;
        $this->x1 = $input;

        $this->y2 = $this->y1;
        $this->y1 = $output;

        return $output;
    }
}


//thanks Chat-GTP, 2-pole optimized for speed
class ButterLPFopt {
    private $samplingFrequency;
    private $cutoffFrequency;
    private $a1;
    private $a2;
    private $b0;
    private $b1;
    private $b2;
    private $x1 = 0.0;
    private $x2 = 0.0;
    private $y1 = 0.0;
    private $y2 = 0.0;

    public function __construct($samplingFrequency, $cutoffFrequency) {
        $this->samplingFrequency = $samplingFrequency;
        $this->cutoffFrequency = $cutoffFrequency;

        $omega_c = 2 * pi() * $this->cutoffFrequency / $this->samplingFrequency;
        $tan_wc_div_2 = tan($omega_c / 2);
        
        $this->a1 = -2 * cos($omega_c) / (1 + $tan_wc_div_2);
        $this->a2 = (1 - $tan_wc_div_2) / (1 + $tan_wc_div_2);
        
        $this->b0 = $tan_wc_div_2 / (1 + $tan_wc_div_2);
        $this->b1 = 2 * $this->b0;
        $this->b2 = $this->b0;
    }

    public function setCutoffFrequency($cutoffFrequency) {
        $this->cutoffFrequency = $cutoffFrequency;
    }

    public function filter($input) {
        $output = $this->b0 * $input + $this->b1 * $this->x1 + $this->b2 * $this->x2
                - $this->a1 * $this->y1 - $this->a2 * $this->y2;
        $output *= 0.5;
        $this->x2 = $this->x1;
        $this->x1 = $input;

        $this->y2 = $this->y1;
        $this->y1 = $output;

        return $output;
    }

}
