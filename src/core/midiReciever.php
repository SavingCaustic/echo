<?php

//aka struct
class MidiMessage {
    //bytes really..
    var int $cmd;
    var int $p1;
    var int $p2;
    var int $p3;
}

class MidiReciever {
    var $playerEngine;

    var $bufferSize;
    var $buffer;
    var $bufferWrIX;
    var $bufferRDIX;
    private MidiMessage $preBuffer;
    var $state;         //CMD, P1, P2, P3, IGNORE
    var $cmdLength;

    function __construct(PlayerEngine $playerEngine) {
        $this->playerEngine = &$playerEngine;
        //state = what part to expect
        $this->state = 'CMD';
        $this->cmdLength = 0;
    }

    function poll() {
        //if there's any data, evaluate!
    }

    function parseByte($val) {
        if ($this->state == 'CMD') {
            $this->parseCmd($val);
        } else {
            $this->parseParam($val);
        }
    }

    function parseCmd($cmd) {
        //do i really have to build this??
        $type = $cmd && 0xf0;
        $cmdLength = 0;
        switch ($type) {
            case 0x80:
            case 0x90:
            case 0xe0:
                $cmdLength = 3;
                break;
            case 0xa0:
                $this->state = 'IGNORE';
                break;
            case 0xb0:
            case 0xc0:
                $cmdLength = 2;
                break;
            case 0xf0:
                switch($cmd) {
                    case 0xfa:
                        //START
                        $this->playerEngine->hTapeController->respondToKey('PLAY');
                        break;
                    case 0xfc:
                        //STOP
                        $this->playerEngine->hTapeController->respondToKey('STOP');
                        break;
                    default:
                        $this->state = 'IGNORE';
                        break;
                }
        }
        if ($cmdLength != 0) {
            //store and make a state change.
            $this->preBuffer->cmd = $cmd;
            $this->cmdLength = $cmdLength;
        }
    }

    function parseParam($val) {
        switch($this->state) {
            case 'P1':
                $this->preBuffer->p1 = $val;
                break;
            case 'P2':
                $this->preBuffer->p2 = $val;
                break;
            case 'P3':
                //??
                $this->preBuffer->p3 = $val;
                break;
        }
        $this->cmdLength--;
        if ($this->cmdLength == 0) {
            //push it to the respective rack no?
            //theRack->addMidi
            $this->state = 'CMD';
        }
    }


}