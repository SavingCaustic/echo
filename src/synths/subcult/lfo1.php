<?php

class LFO1 {
    //voice lfo. Induvidual for each voice in patch
    //but the settings are global. So.. Really just the ramp and oscillator that are induvidual

    //references and objects
    var $p_core;    //pointer to core
    var $o_osc;     //object
    var $o_ar;      //object

    //parameters
    var $rate;      //rate in mS or what's fastest for DSP
    var $depth;     //unit 0-1 or -1 - 1 ? 
    var $target;    //enum value

    function __construct($core) {
        $this->core = &$core;
    }

    function setValues($rate , $depth, $shape, $target, $ramp = 0) {
        //master blaster
        $this->rate = $rate;
        $this->depth = $depth;
        $this->shape = $shape;
        $this->target = $target;
        $this->ramp = $ramp;
    }
}

class Voice {
    //really no METHODS needed nere since it's all shared.
    var $o_lfo;     //here's lfo1
    var $o_ar;      //rise for lfo1
    var $o_osc1;
    var $o_osc2;
    var $vco;
    var $vcf;
    var      
}