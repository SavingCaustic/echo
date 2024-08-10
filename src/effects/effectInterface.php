<?php

interface EffectInterface {
    //the following methods must be implemented by any effect

    function reset();
    //any initalization that could be re-run.
    //called at end of __construct

    function setParam($name, $val);     
    //set param in 'patch cache'

    function pushAllParams();
    //push param to speed optimized register

    //function process(&$ptrBuffer);
    function process(&$bufferOut);
    //dsp process

    function processClock();
}

