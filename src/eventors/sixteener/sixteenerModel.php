<?php

class SixteenerModel extends ParamsAbstract implements EventorInterface {
    //super simple eventor to verify that the timing works also when not playing
    /**
     * @var Rack                  //fixes syntax in VS Code
     */
    var $hRack;
    var $position;
    
    var $clockDivider;
    var $notes; //array of notes with velocety to process
    var $noteCount;
    var $controllers; //array of controller values to process


    function __construct($rack,$position) {
        $this->hRack = &$rack;
        $this->position = $position;
        $this->reset(); //dunno..
        $this->pushAllParams();
    }

    function reset() {
        //setup array of notes as available
        $this->notes = array();
        $this->noteCount = 0;
        $this->clockDivider = 0;
    }

    function tick() {}

    function pushNumParam($name, $val) {
    }

    function pushStrParam($name, $val) {
    }

    function parseMidi() {
        //act on midi command. yeah? don't get it really.
    }

    function sendMidi($cmd, $param1 = 0, $param2 = 0) {
        //this method is shared across all eventors so could be in rack actually..
        if (($this->position == 1) && (!is_null($this->hRack->hEventor2))) {
            $this->hRack->hEventor2->parseMidi($cmd, $param1, $param2);
        } else {
            $this->hRack->hSynth->parseMidi($cmd, $param1, $param2);
        }
    }

    function processClock() {
        //requested on any either 0xf8 or the rack clock?
        $clock = $this->clockDivider;
        $q = 6;     //testing with eights (12), yes 6 = 16ths
        $note = 46;
        $vel = rand(80,100);
        if ($clock % $q == 0) {
            $this->sendMidi(0x90, $note, $vel);
        }
        if ($clock % $q == 2) {
            $this->sendMidi(0x80, $note,0);
        }
        $this->clockDivider = ($this->clockDivider + 1) % $q;
    }

    function play() {
        //ok, we need this method?
    }

    function stop() {}

    function panic() {
        //the panic signal is sent to the rack and re-distributed to the eventor, synth, and effects
        //just close any open states. Leave it to the synth to any running voices.
    }
}

