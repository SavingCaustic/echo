<?php
declare(strict_types=1);

interface SynthInterface {
    //the following methods must be implemented by any synth
    function reset();                    //to be called on essential config changes

    function setParam($name, $val);

    function pushAllParams();           //needed by patch-load?

    function parseMidi($cmd, $param1 = null, $param2 = null);

    function renderNextBlock();

    //function noteOn($note, $vel);

    //function noteOff($note, $vel);

}