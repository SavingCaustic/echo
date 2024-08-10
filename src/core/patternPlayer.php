<?php
require 'pattern.php';

class PatternPlayer {
    /**
     * @var Rack                //fixes syntax in VS Code
     */
    var $hRack;
    var $playerEngine;
    /**
     * @var Pattern[]           //fixes syntax in VS Code
     */
    var $patterns;              //objects, but in c++, these will be pointers.
    /**
     * @var Pattern             //fixes syntax in VS Code
     */
    var $activePattern;         //a ptr to a struct
    var $nextPattern;           //a ptr to a struct

    var $ixActivePattern;       //pointer to the active pattern
    var $ixNextPattern;         //pointer to the next pattern
    var $eightCounter;
    var $swingDebug;
    var $nextTickPulse;
    var $nextTickOrigin;

    function __construct($rack) {
        $this->hRack = &$rack;
        $this->playerEngine = $this->hRack->playerEngine;
        //
        $this->patterns = array(
            new Pattern(),
            new Pattern()
        );
        $this->setActivePattern(0);
    }

    function reset() {
        //dunno what to do here really. When is it called?
    }

    function clockReset() {
        //position the ptr to the first element and update the nextClick
        //quite easy. Pre-roll notes are negative in time so skip these.
        //looking for pre-roll notes in next pattern can't be now since it could/should
        //be loaded later. Maybe a method for that, loadNextPattern(), updating prerollPtr.
        //should pre-roll notes be played if pattern is looping? No. Seems wrong..
        $max = sizeof($this->activePattern->data);
        $test = 0;
        $this->activePattern->EOF = true;
        for ($i = 0; $i < $max; $i++) {
            $test = $this->activePattern->data[$i][0];
            if ($test >= 0) {
                //found
                $this->activePattern->EOF = false;
                break; //the for-loop
            }
        }
        $this->activePattern->dataPtr = $i;
        $this->calcNextEventTick($test);
        $this->eightCounter = 0;
    }

    function incEightCounter() {
        $this->eightCounter++;
        $p = &$this->activePattern;
        //handle empty patterns correctly
        if ($p->EOF && sizeof($p->data) > 0) {
            if ($this->eightCounter * 12 * TPH_TICKS_PER_CLOCK == $p->barsInPattern * $p->ticksInBar) {
                $p->dataPtr = 0;
                $this->eightCounter = 0;
                //this should really scan pass any negative start-marks
                while ($p->data[$p->dataPtr][0] < 0) {
                    $p->dataPtr++;
                }
                $this->calcNextEventTick($p->data[$p->dataPtr][0]);
                $this->activePattern->EOF = false;
            }
        }
    }

    function setActivePattern($ix = 0) {
        if ($ix == 0) {
            $this->activePattern = &$this->patterns[0];
            $this->nextPattern = &$this->patterns[1];
        } else {
            $this->activePattern = &$this->patterns[1];
            $this->nextPattern = &$this->patterns[1];
        }
        $this->ixActivePattern = $ix;
        $this->ixNextPattern = 1 - $ix;
    }

    function loadPatternFromJSON($jsonData, $next = false) {
        //called from low-priority thread
        $data = json_decode($jsonData, true);
        $barCount = $data['barCount'];
        $signNom = $data['signNom'];
        $signDenom = $data['signDenom'];
        $ticksInBar = 24 * TPH_TICKS_PER_CLOCK * 4 * $signNom / $signDenom;

        $notes = $data['notes'];
        //add note-offs based on note length
        $out = array(); //could be set to twice the size of data.
        //id *could* be generated by time_note. maybe..
        foreach ($notes as $elm) {
            //elm: timecode|length|note|vel
            //ignore notes out of bound
            if ($elm['tick'] < $barCount * $ticksInBar) {
                $on = array($elm['tick'], 0x90, $elm['note'], $elm['vel']);
                $offTick = $elm['tick'] + $elm['len'];
                if ($offTick >= $barCount * $ticksInBar) {
                    $offTick = $barCount * $ticksInBar - 1;
                }
                $off = array($offTick, 0x80, $elm['note'], '0x00');
                $out[] = $on;
                $out[] = $off;
            }
        }
        //we need to sort it on timecode
        //out: timecode|cmd|p1|p2
        $sortCol = array_column($out, 0);
        array_multisort($sortCol, SORT_ASC, $out);

        if ($next) {
            $p = $this->nextPattern;
        } else {
            $p = $this->activePattern;
        }
        $p->data = $out;
        $p->ticksInBar = $ticksInBar;
        $p->barsInPattern = $barCount;
        $p->patternPtr = 0;
        $p->patternTick = 0;
        $p->EOF = false; //could maybe be true if be load an empty pattern?

        //this should really scan pass any negative start-marks.
        while ($p->data[$p->patternPtr][0] < 0) {
            $p->patternPtr++;
        }
    }

    function probeNewTick($rotatorPulse) {
        $pulse = $rotatorPulse + 12 * TPH_TICKS_PER_CLOCK * $this->eightCounter;
        $this->swingDebug = false;
        if ($pulse >= $this->nextTickPulse) {
            //next tick to process is *swung*
            $originalPulse = $this->nextTickOrigin; //unswung pulse..
            //what pattern are we looking in, we have two..
            $p = $this->activePattern;
            while (!$p->EOF && ($p->data[$p->dataPtr][0] == $originalPulse)) {
                //process pattern event
                $evt = $p->data[$p->dataPtr];
                $cmd = $evt[1] & 0xf0;
                switch ($cmd) {
                    case 0x90:
                        $this->hRack->parseMidi(0x90, $evt[2], $evt[3]);
                        if ($this->swingDebug) echo 'sending note on at tick ' . $pulse . "\n";
                        break;
                    case 0x80:
                        $this->hRack->parseMidi(0x80, $evt[2], 0);
                        if ($this->swingDebug) echo 'sending note off at tick ' . $pulse . "\n";
                        break;
                }
                $p->dataPtr++;
                if ($p->dataPtr >= sizeof($p->data)) {
                    $p->EOF = true;
                }
            }
            //calc next..
            if ($p->EOF) {
                //the reset will happen on next eightInc so do nothing now..
            } else {
                $nextPulse = $p->data[$p->dataPtr][0];
                $this->calcNextEventTick($nextPulse);
            }
        }
    }

    function calcNextEventTick($pulse) {
        //look in pattern, and based on swing setting, calculate the swung pulse.
        if ($this->hRack->swingOverride) {
            //calc swing based on rack
            $swingCycle = $this->hRack->swingCycle;
            $swingDepth = $this->hRack->swingDepth;
        } else {
            //calc swing based on PE
            $swingCycle = $this->playerEngine->swingCycle;
            $swingDepth = $this->playerEngine->swingDepth;
        }
        $this->nextTickPulse = $this->calcSwungTick($pulse, $swingCycle, $swingDepth);
        /*bad code: if ($this->nextTickPulse > $this->playerEngine->hRotator->pulsesInBar) {
            $this->nextTickPulse = $this->nextTickPulse - $this->playerEngine->hRotator->pulsesInBar;
        }*/
        $this->nextTickOrigin = $pulse;
    }

    function calcSwungTick($pulse, $swingCycle, $swingDepth) {
        $angle = ($pulse % ($swingCycle * TPH_TICKS_PER_CLOCK)) / ($swingCycle * TPH_TICKS_PER_CLOCK);
        $swing = (0.5 - cos($angle * pi() * 2) * 0.5) * $swingDepth * $swingCycle * TPH_TICKS_PER_CLOCK / 4;
        $nextTickPulse = $pulse + round($swing, 3);
        return $nextTickPulse;
        //NOTE THAT RECORDING NOTES NEED CALC OTHER WAY using the integral of above
    }
}
