<?php

interface EffectInterface {
    //the following methods must be implemented by any effect

    function reset();
    //any initalization that could be re-run.
    //called at end of __construct

    //function process(&$ptrBuffer);
    function process(&$bufferOut);
    //dsp process

    function processClock();
}

