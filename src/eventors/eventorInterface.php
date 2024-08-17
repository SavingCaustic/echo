<?php
declare(strict_types=1);

interface EventorInterface {
    //the following methods must be implemented by any event-processor (eventor)

    function reset();

    function parseMidi();

    function sendMidi($cmd, $param1 = 0, $param2 = 0);

    function processClock();
    
    function tick();
    //probably called from F8 but who knows, right?

    function play();

    function stop();
    
    function panic();
}