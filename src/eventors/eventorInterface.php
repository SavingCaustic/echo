<?php
declare(strict_types=1);

interface EventorInterface {
    //the following methods must be implemented by any event-processor (eventor)

    function reset();

    function setParam($name, $val);

    function pushParams();

    function parseMidi();

    function sendMidi();

    function processClock();
    
    function tick();
    //probably called from F8 but who knows, right?

    function play();

    function stop();
    
    function panic();
}