<?php
declare(strict_types=1);

//maybe extract any pattern related stuff and have a separate PatternPlayer
require(__DIR__ . '/dspCore.php');
require(__DIR__ . '/patternPlayer.php');
require(__DIR__ . '/../synths/synthInterface.php');
require(__DIR__ . '/../eventors/eventorInterface.php');
require(__DIR__ . '/../effects/effectInterface.php');

//maybe this class should be split into a rack- and a pattern-player class.
//any settings here??

class Rack {
    var $playerEngine;

    var $rackIdx;
    var $dspCore;
    //                      
    var $swingOverride;
    var $swingCycle;        //in clocks
    var $swingDepth;        //0 - 1
    var $swingDebug = false;

    var $bufferOut;
    //
    var $hPatternPlayer;    //we may not use it on eventors but keeps the code small.
    var $nextPattern;       //array of next pattern events (to be written)
    //
    var $nextClockPulse;    //
    var $clock24;           //We're keeping this for swing to operate correctly when phase is a quarter (8th swing)
                            
    var $nextTickPulse;
    var $nextTickOrigin;    //silly name but the uswung pulse so we may iterate over the (unswung) notes-array 

    /**
     * @var EventorInterface             
     */
    var $hEventor1;
    var $hEventor2;
    /**
     * @var SynthInterface             
     */
    var $hSynth;
    /**
     * @var EffectInterface             
     */
    var $hEffect1;
    var $hEffect2;

    function __construct($rackIdx, &$playerEngine) {
        //store so we now outselfs which rack we're at.
        $this->rackIdx = $rackIdx;
        $this->playerEngine = &$playerEngine;
        //i want dspCore to GO AWAY
        $this->dspCore = new DSPCore(TPH_SAMPLE_RATE, $this->playerEngine->masterTune, TPH_RACK_RENDER_SIZE, $this->playerEngine->appDir);
        $this->hPatternPlayer = new PatternPlayer($this);
        //these are not mandatory.
        $this->hSynth = null;
        $this->hEventor1 = null;
        $this->hEventor2 = null;
        $this->swingDebug = false;
        $this->reset();
    }

    function reset() {
        $this->swingOverride = false;
        $this->swingCycle = 96;
        $this->swingDepth = 0;
        $this->clock24 = 0;
        $this->bufferOut = array_fill(0, TPH_RACK_RENDER_SIZE, 0);
        $this->hPatternPlayer->reset();
    }

    function loadEventor($eventorName, $slot = 1) {
        require_once(__DIR__ . '/../eventors/' . $eventorName . '/' . $eventorName . 'Model.php');
        $class = $eventorName . 'Model';
        if ($slot != 2) {
            $this->hEventor1 = new $class($this, 1);
            return $this->hEventor1;  //not sure about this one..
        } else {
            $this->hEventor2 = new $class($this, 2);
            return $this->hEventor2;  //not sure about this one..
        }
    }

    function unloadEventor($slot = 1) {
        //delete[]
        if ($slot != 2) {
            $this->hEventor1 = null;
        } else {
            $this->hEventor2 = null;
        }
    }


    function loadSynth($synthName) {
        require_once($this->playerEngine->appDir . '/src/synths/' . $synthName . '/' . $synthName . 'Model.php');
        //name of model to avoid name-conflicts?
        $class = $synthName . 'Model';
        $this->hSynth = new $class($this->dspCore);
        // should call ->reset on construct $this->hSynth->init();
    }

    function unloadSynth() {
        //really? Why..
    }


    function loadEffect($effectName, $slot = 1) {
        require_once(__DIR__ . '/../effects/' . $effectName . '/' . $effectName . 'Model.php');
        $class = $effectName . 'Model';
        if ($slot != 2) {
            $this->hEffect1 = new $class($this);
            return $this->hEffect1;  //not sure about this one..
        } else {
            $this->hEffect2 = new $class($this);
            return $this->hEffect2;  //not sure about this one..
        }
    }

    function unloadEffect($slot = 1) {
        //delete[]
        if ($slot != 2) {
            $this->hEffect1 = null;
        } else {
            $this->hEffect2 = null;
        }
    }

    function loadPatternFromJSON($jsonData, $next = false) {
        //ne need to look at both next and which pattern is active.
        $this->hPatternPlayer->loadPatternFromJSON($jsonData);
    }

    function getSynthRef() {
        return $this->hSynth;
    }

    //timing functions

    function clockReset() {
        //dunno.. on play to sync stuff..
        $this->nextClockPulse = 0;
        $this->clock24 = 0;
        $this->hPatternPlayer->clockReset();
        //$this->currTick = 0;
        //$this->pulse = 0;
    }

    function probeNewClock($pulse) {
        //currently no support for override
        //Clock not related to pattern but may have swing so how to do it?!
        //if wrapping, wait for pulse to turn around.. 50 smells..
        if ($pulse - $this->nextClockPulse > 50) return;
        if ($pulse >= $this->nextClockPulse) {
            //that's it. the actual calculatiion is done at render end.
            if (!is_null($this->hEventor1)) $this->hEventor1->processClock();
            if (!is_null($this->hEventor2)) $this->hEventor2->processClock();
            //we should query effects first after synth has been processed right?
            //or no? We set them now, faster response.
            if (!is_null($this->hEffect1)) $this->hEffect1->processClock();
            if (!is_null($this->hEffect2)) $this->hEffect2->processClock();
            //now we need a new pulseNextClock
            $this->clock24++;
            if ($this->clock24 == 24) {
                $this->clock24 = 0;
            }
            $this->calcNextClockPulse();
            //update the clock, which is always PPQN24. 
            //maybe there is no counter? because then we would need a max-val based on time-sign.
        }
    }

    function calcNextClockPulse() {
        //calculate what mPulse the next clock will happen.
        if ($this->swingOverride) {
            //calc swing based on rack
            $swingCycle = $this->swingCycle;
            $swingDepth = $this->swingDepth;
        } else {
            //calc swing based on PE
            $swingCycle = $this->playerEngine->swingCycle;
            $swingDepth = $this->playerEngine->swingDepth;
        }
        $this->nextClockPulse = $this->calcSwungClock($swingCycle, $swingDepth);
        //we should probably send midi clock here, at least when playing.
    }

    function calcSwungClock($swingCycle, $swingDepth) {
        $angle = ($this->clock24 % $swingCycle) / $swingCycle;
        $swing = (0.5 - cos($angle * pi() * 2) * 0.5) * $swingDepth * $swingCycle / 4;
        $nextClockPulse = ($this->clock24 + $swing) * TPH_TICKS_PER_CLOCK;
        //echo 'At angle ' . $angle . ', swing is: ' . $swing . ', so next clock at ' . $nextClockPulse . "\r\n";
        if ($nextClockPulse >= 96) $nextClockPulse -= 96;
        return $nextClockPulse;
    }

    function probeNewTick($pulse) {
        $this->hPatternPlayer->probeNewTick($pulse);
        //dunno what to return really.
    }


    //these pattern-functions - what to do..

    function loadPattern($pattern, $barCount, $signNom = 4, $signDenom = 4) {
        //here timing should be 96PPQN always.
        //signnom and denom should really default to signature of playerEngine
        die('depreacted');
        //$this->hPatternPlayer->loadPattern($pattern, $barCount, $signNom, $signDenom);
    }

    function parseMidi($command, $param1, $param2) {
        //from somewhere, (midi, screen-keyboard or pattern), a midi-event happened. Process it. 
        if (is_null($this->hEventor1)) {
            $this->hSynth->parseMidi($command, $param1, $param2);
        } else {
            $this->hEventor1->parseMidi($command, $param1, $param2);
        }
    }


    function render($blocks) {
        //blocks could be useful for pre-rendering of background tracks..
        for ($i = 0; $i < $blocks; $i++) {
            $this->hSynth->renderNextBlock();
            $this->bufferOut = $this->hSynth->buffer;
            if (!is_null($this->hEffect1)) {
                $this->hEffect1->process($this->bufferOut);
            }
            if (!is_null($this->hEffect2)) {
                $this->hEffect2->process($this->bufferOut);
            }
        }
    }

    function loadPatch($target, $patchName) {
        //decide type from name
        switch ($target) {
            case 'eventor1':
            case 'eventor2':
                //load eventorPatch
                break;
            case 'synth':
                //load synthPatch (based on type?)
                break;
            case 'effect1':
            case 'effect2':
                //load effect patch - really??
                break;
            default:
                //unknown target, just ignore..
                break;
        }
    }


    function saveRackPatch($name) {
        //ok, all settings for eventors etc should be saved as a rack-patch..
    }

    function loadRackPatch($name) {
        //this is better. No effect patches and bla bla bla...
    }
}
