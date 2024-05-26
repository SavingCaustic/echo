<?php
//retrigger. Any note being held will be fired as on at let's say every 8th.
//this is akward for it means that we're inserting dta on th emidi-bus
//we don't want that on a global level or?

class OctaverModel {
    //do we need ref to dspCore?

    function __construct($rack,$position) {
        $this->rack = &$rack;
        $this->position = $position;
        $this->reset();
    }

    function reset() {
        //TIMING IN 24 PPQN in eventors
        $this->settings = array(
            'NOTE_LENGTH' => 3,
            'FREQ' => 12,
        );
        //save these default settings to be picked up by www-player
        file_put_contents(__DIR__ . '/defaults.json',json_encode($this->settings));
    }

    function pushSettings() {
        //experimental function that pushes settings to non-readable, optimized registers.
        $se = $this->settings;
        $this->note_length = $se['NOTE_LENGTH'];
    }

    function parseMidi($cmd, $param1 = null, $param2 = null) {
        //act on midi command.
        //maybe doesn't alter the midi-stream but affects the synth?
        //simple example. No FSM
        $cmdMSN = $cmd & 0xf0;
        switch ($cmdMSN) {
            case 0x80:
            case 0x90:
                //clone note +12
                $this->sendMidi($cmd, $param1, $param2);
                $this->sendMidi($cmd, $param1 + 12, $param2);
                break;
            default:
                $this->sendMidi($cmd, $param1, $param2);
        }
    }

    function sendMidi($cmd, $param1 = null, $param2 = null) {
        if (($this->position == 1) && (!is_null($this->rack->hEventor2))) {
            $this->rack->hEventor2->parseMidi($cmd, $param1, $param2);
        } else {
            $this->rack->hSynth->parseMidi($cmd, $param1, $param2);
        }
    }

    function processClock() {
        //requested on any clock.
    }

    function play() {}

    function stop() {}

    function panic() {
        //the panic signal is sent to the rack and re-distributed to the eventor, synth, and effects
        //just close any open states. Leave it to the synth to any running voices.
    }
}

