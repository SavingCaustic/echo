<?php
//this filter class was more or less written by ChatGTP.
//It could possilby be highly optimized.


class SubrealFilter {
    const LOW_PASS = 1;
    const HIGH_PASS = 2;
    const BAND_PASS = 3;

    private $type;
    private $cutoff;
    private $resonance;
    private $sampleRate;
    private $c;
    private $a1;
    private $a2;
    private $a3;
    private $b1;
    private $b2;
    private $out1;
    private $out2;

    public function __construct($sampleRate = 48000) {
        $this->sampleRate = $sampleRate;
    }

    public function setParams($type, $cutoff, $resonance) {
        switch($type) {
            case 'LOWPASS':
                $this->type = self::LOW_PASS;
                break;
            case 'LOWPASS':
                $this->type = self::LOW_PASS;
                break;
            case 'LOWPASS':
                $this->type = self::LOW_PASS;
                break;
        }
        $this->calcCoefficients($cutoff, $resonance);

        // Initialize previous output values
        $this->out1 = 0;
        $this->out2 = 0;
    }

    function calcCoefficients($cutoff, $resonance = null) {
        $this->cutoff = $cutoff;
        if (!is_null($resonance)) $this->resonance = $resonance;
        // Pre-calculate values for filter coefficients
        $this->c = 1.0 / tan(M_PI * $this->cutoff / $this->sampleRate);
        $this->a1 = 1.0 / (1.0 + $this->resonance * $this->c + $this->c * $this->c);
        $this->a2 = 2 * $this->a1;
        $this->a3 = $this->a1;
        $this->b1 = 2.0 * (1.0 - $this->c * $this->c) * $this->a1;
        $this->b2 = (1.0 - $this->resonance * $this->c + $this->c * $this->c) * $this->a1;
    }

    function reset() {  //needed? Not used..
        $this->out1 = 0;
        $this->out2 = 0;        
    }

    public function applyFilter($input) {
        switch ($this->type) {
            case self::LOW_PASS:
                $output = $this->a1 * $input + $this->a2 * $this->out1 + $this->a3 * $this->out2 - $this->b1 * $this->out1 - $this->b2 * $this->out2;
                break;
            case self::HIGH_PASS:
                $output = $this->a1 * $input + $this->a2 * $this->out1 + $this->a3 * $this->out2 - $this->b1 * $this->out1 - $this->b2 * $this->out2;
                $output = $input - $output;
                break;
            case self::BAND_PASS:
                $output = $this->a1 * $input + $this->a2 * $this->out1 + $this->a3 * $this->out2 - $this->b1 * $this->out1 - $this->b2 * $this->out2;
                $output = $this->resonance * ($output - $this->out2) + $input;
                break;
            default:
                $output = $input;
                break;
        }

        // Update previous output values
        $this->out2 = $this->out1;
        $this->out1 = $output;

        return $output;
    }
}



class SubrealFilter2 { //MoogFilter
    private $cutoff;
    private $resonance;
    private $sampleRate;
    private $f;
    private $p;
    private $q;
    private $buf0;
    private $wbuf1;
    private $buf2;
    private $buf3;

    public function __construct($sampleRate) {
        $this->sampleRate = $sampleRate;
    }

    public function setParams($type, $cutoff, $resonance) {
        $sampleRate = $this->sampleRate;
        $this->cutoff = 2 * cos(M_PI * min(0.25, max(0, $cutoff / $sampleRate)));
        $this->resonance = $resonance;
        $this->f = 0;
        $this->p = 0;
        $this->q = 0;
        $this->buf0 = 0;
        $this->buf1 = 0;
        $this->buf2 = 0;
        $this->buf3 = 0;
    }

    public function applyFilter($input) {
        $input -= $this->resonance * $this->buf3;
        $input *= 0.35013 * ($this->cutoff * $this->cutoff) * ($this->cutoff * $this->cutoff);
        $this->buf0 += $this->f * ($input - $this->buf0 + $this->p * ($this->buf0 - $this->buf1));
        $this->buf1 += $this->f * ($this->buf0 - $this->buf1);
        $this->buf2 += $this->f * ($this->buf1 - $this->buf2);
        $this->buf3 += $this->f * ($this->buf2 - $this->buf3);
        $this->buf3 = $input;
        $this->f = $this->f + $this->p * (0.3 * ($this->buf0 - $this->buf3) - $this->q * $this->f);
        $this->p = (1 - $this->cutoff) * 1.5 - 0.5 * $this->f;
        $this->q = (0.5 - $this->resonance * 0.5) * (1 - 0.5 * $this->f - 0.5 * $this->f * $this->f);
        return $this->buf3;
    }
}

