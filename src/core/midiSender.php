<?php


class MidiSender {
    var $playerEngine;

    function __construct(PlayerEngine $playerEngine) {
        $this->playerEngine = &$playerEngine;
    }

}