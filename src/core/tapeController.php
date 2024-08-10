<?php

class TapeController {
    // STATES: STOPPED, PLAY_REQUESTED, STOP_REQUESTED, PAUSE_REQUESTED, PLAYING, PAUSED
    private $playerEngine;
    private $state;

    public function __construct(PlayerEngine &$playerEngine) {
        $this->playerEngine = $playerEngine;
        $this->state = 'STOPPED';
    }

    public function respondToKey($key) {
        //we have no pause button.
        $state = $this->getState();
        //tempting to compound $key and $state but they will be real enums later..
        switch ($state) {
            case 'STOPPED':
                switch ($key) {
                    case 'STOP':
                        //audio driver restart
                        break;
                    case 'PLAY':
                        //so this is not paused right?
                        $this->state = 'PLAY_REQUESTED';
                        break;
                    case 'RECORD':
                        $this->state = 'STOPPED_RECORD_REQUESTED';
                        break;
                    default:
                        //ignore
                        break;
                }
                break;
            case 'PLAYING':
                switch ($key) {
                    case 'STOP':
                        $this->state = 'STOP_REQUESTED';
                        break;
                    case 'PLAY':
                        $this->state = 'PAUSE_REQUESTED';
                        break;
                    case 'RECORD':
                        $this->state = 'PLAYING_RECORD_REQUESTED';
                }
                break;
            case 'PAUSED':
                switch ($key) {
                    case 'STOP':
                        $this->state = 'STOP_REQUESTED';
                        break;
                    case 'PLAY':
                        $this->state = 'RESUME_REQUESTED';
                        break;
                }
                break;
            case 'RECORDING':
                switch ($key) {
                    case 'STOP':
                        $this->state = 'HMM. whats the state';
                        break;
                    case 'RECORD':
                        $this->state = 'UNRECORD_REQUESTED';
                        break;
                }
                break;
            case 'PLAY_REQUESTED':
                switch($key) {
                    case 'STOP':
                        //since we haven't started it should be ok just to stop right?
                        $this->state = 'STOPPED';
                        break;
                }
        }
        //now finally, if state has changed during this trip, process it.
        if ($state != $this->state) {
            $this->respondToTransition();
        }
    }

    private function respondToTransition($depth = 0) {
        if ($depth > 5) {
            $this->playerEngine->hErrorLog->add('stack err in respondToTransition');
            return;
        }
        $state = $this->getState();
        //tempting to compound $key and $state but they will be real enums later..
        switch ($state) {
            case 'PLAY_REQUESTED':
                //??$this->playerEngine->syncAllRacks();
                $this->playerEngine->isPlaying = true;
                $this->playerEngine->clockReset();
                $this->state = 'PLAYING';
                break;
            case 'STOP_REQUESTED':
                //if ok to stop:
                $this->playerEngine->isPlaying = false;
                break;
            case 'PAUSE_REQUESTED':
                //if ok to pause:
                $this->playerEngine->isPlaying = false;
                break;
            case 'RESUME_REQUESTED':
                //if ok to pause:
                $this->playerEngine->isPlaying = true;
                break;
            case 'RECORD_REQUESTED':
                //to be written..                
            case 'UNRECORD_REQUESTED':
                //to be written..
            default:
                //echo 'really stupid recusive call to ' . $state . "\r\n";
        }
        if ($this->state != $state) {
            $this->respondToTransition($depth++);
        }
    }

    public function getState() {
        return $this->state;
    }

    public function setState($newState) {
        //dunno if this is needed. 
        $this->state = $newState;
    }
}
