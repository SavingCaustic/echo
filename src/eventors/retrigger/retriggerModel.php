<?php

class RetriggerModel {
    //do we need ref to dspCore?
    var $notes; //array of notes with velocety to process
    var $noteCount;
    var $params;
    var $maxNotes;
    var $controllers; //array of controller values to process

    function __construct($rack,$position) {
        $this->rack = &$rack;
        $this->position = $position;
        $this->reset(); //dunno..
        $this->pushSettings();
    }

    function reset() {
        //setup array of notes as available
        $this->notes = array();
        $this->maxNotes = 8;
        for($i=0;$i < $this->maxNotes;$i++) $this->notes[] = array(60,0);
        $this->noteCount = 0;
    }

    function pushSettings() {
        //experimental function that pushes settings to non-readable, optimized registers.
        $se = $this->params;
        $this->note_length = 10; //$se['NOTE_LENGTH'];
    }

    function parseMidi($cmd, $param1 = null, $param2 = null) {
        //act on midi command.
        //maybe doesn't alter the midi-stream but affects the synth?
        //simple example. No FSM
        $cmdMSN = $cmd & 0xf0;
        switch ($cmdMSN) {
            case 0x90:
                //add note to list. Since play does reset, we should really look for duplets..
                //if notes is sorted, we would find it.
                $noteAdd = true;
                for($i=0;$i<$this->noteCount;$i++) {
                    if ($this->notes[$i][0] == $param1) {
                        //ooops, double trigger, do nothing..
                        $noteAdd = false;
                    }
                }
                if ($noteAdd) {
                    //only add if we have room for it
                    if ($this->noteCount < $this->maxNotes) {
                        $this->notes[$this->noteCount] = array($param1, $param2);
                        $this->noteCount++;
                    }
                    break;
                }
                $retVal = array(array());
                break;
            case 0x80:
                //try to remove note from list
                for($i=0;$i<$this->noteCount;$i++) {
                    if ($this->notes[$i][0] == $param1) {
                        //release and defrag.
                        $j = $this->noteCount - 1;
                        $this->notes[$i] = $this->notes[$j];
                        $this->noteCount--;
                        break;
                    }
                }
                $retVal = array(array());
                break;
            case 0xf0:
                switch($cmd) {
                    case 0xf8:
                        //clock..
                        //dunno about this but: $this->processClock();
                        break;
                }
                break;
            default:
                $retVal = array(array($cmd, $param1, $param2));
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
        //requested on any either 0xf8 or the rack clock?
        $clock = $this->rack->rackClock;
        if ($clock % 24 == 0) {
            for($i=0;$i<$this->noteCount;$i++) {
                $note = $this->notes[$i];
                $this->sendMidi(0x90, $note[0], $note[1]);
            }
        }
        if ($clock % 24 == 20) {
            for($i=0;$i<$this->noteCount;$i++) {
                $note = $this->notes[$i];
                $this->sendMidi(0x80, $note[0],0);
            }
        }
    }

    function play() {
        $this->clickCount = 0;
    }

    function stop() {}

    function panic() {
        //the panic signal is sent to the rack and re-distributed to the eventor, synth, and effects
        //just close any open states. Leave it to the synth to any running voices.
    }
}

