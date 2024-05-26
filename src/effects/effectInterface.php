<?php

interface EffectInterface {
    //the following methods must be implemented by any effect

    function reset();
    //any initalization that could be re-run.
    //called at end of __construct

    function setParam($name, $val, $push = true);     
    //set param in 'patch cache'

    function pushParam($name,$val);
    //push param to speed optimized register

    function pushParams();
    //iterate over all parameters and push

    //function process(&$ptrBuffer);
    function process(&$bufferOut);
    //dsp process

}

