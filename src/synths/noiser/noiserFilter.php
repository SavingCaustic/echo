<?php

class ButterworthFilter {
    private $b = [];
    private $a = [];
    private $type;
    private $center_freq;
    private $sample_freq;
    private $Q;
    private $x = [0, 0];  // Previous input values (state)
    private $y = [0, 0];  // Previous output values (state)

    public function __construct() {
    }

    function calculateCoefficients($center_freq, $sample_freq, $Q, $type) {
        $this->center_freq = $center_freq;
        $this->sample_freq = $sample_freq;
        $this->Q = $Q;
        $this->type = $type;
        //
        $nyquist = 0.5 * $this->sample_freq;
        $omega = 2.0 * pi() * $this->center_freq / $this->sample_freq;
        $alpha = sin($omega) / (2.0 * $this->Q);

        if ($this->type == 'bandstop') {
            $b0 = 1;
            $b1 = -2 * cos($omega);
            $b2 = 1;
            $a0 = 1 + $alpha;
            $a1 = -2 * cos($omega);
            $a2 = 1 - $alpha;
        } elseif ($this->type == 'bandpass') {
            $b0 = $alpha;
            $b1 = 0;
            $b2 = -$alpha;
            $a0 = 1 + $alpha;
            $a1 = -2 * cos($omega);
            $a2 = 1 - $alpha;
        } else {
            throw new Exception("Invalid filter type: {$this->type}");
        }

        // Normalize the filter coefficients
        $this->b = [$b0 / $a0, $b1 / $a0, $b2 / $a0];
        $this->a = [1, $a1 / $a0, $a2 / $a0];
    }

    public function applyFilter(&$data,$mix) {
        //this could really be an effect instead but for now..
        for($i = 0; $i < TPH_RACK_RENDER_SIZE * 2; $i = $i + 2) {
            //run mono signal through filter
            $value = ($data[$i] + $data[$i+1]) / 2;
            $filtered_value = $this->b[0] * $value + $this->b[1] * $this->x[0] + $this->b[2] * $this->x[1] - $this->a[1] * $this->y[0] - $this->a[2] * $this->y[1];
            $data[$i] = $filtered_value * $mix + $data[$i] * (1-$mix);
            $data[$i+1] = $filtered_value * $mix + $data[$i+1] * (1-$mix);

            // Shift the old values
            $this->x[1] = $this->x[0];
            $this->x[0] = $value;

            $this->y[1] = $this->y[0];
            $this->y[0] = $filtered_value;
        }
    }
}

