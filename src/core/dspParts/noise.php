<?php

class NoiseOsc {
    var $dspCore;
    var $seed;
    var $a, $b, $c;

    function __construct($dspCore) {
        $this->dspCore = &$dspCore;
        $this->seed = 235325325;
        $this->a = 1664525;
        $this->b = 1013904223;
        $this->c = pow(2, 24); // was 32
      }
    

    function getNextSample() {
        $this->seed = ($this->a * $this->seed + $this->b) % $this->c;
        return $this->seed / $this->c * 2 - 1;
    }

    function getSamples($cnt) {
        $samples = array();
        for($i=0;$i<$cnt;$i++) {
            $this->seed = ($this->a * $this->seed + $this->b) % $this->c;
            $samples[] = $this->seed / $this->c * 2 - 1;
        }
        return $samples;
    }
}