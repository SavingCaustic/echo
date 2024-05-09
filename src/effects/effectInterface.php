<?php
declare(strict_types=1);

interface EffectInterface {
    //the following methods must be implemented by any synth

    function initSettings();

    function setParam($name, $val);

    function pushSettings();

    function process();

}

