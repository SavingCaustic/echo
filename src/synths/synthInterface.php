<?php
declare(strict_types=1);

interface SynthInterface {
    //the following methods must be implemented by any synth
    function init();                    //to be called on essential config changes

    function initSettings();

    function setParam($name, $val);

    function pushSettings();

    function noteOn($note, $vel);

    function noteOff($note, $vel);

    function renderNextBlock();
}