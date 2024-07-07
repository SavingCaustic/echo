<?php

class Sequencer {
    var $patterns;
    var $automations;
    var $notes;

    function __construct() {
        //not static..
    }

    function getNextAutomation($name,$currTick) {
        //look for next automation in time
        //ok, we've loaded the automations using json?
        //calculate delta-time
    }
}