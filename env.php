<?php

// run this from terminal using:
// php -dxdebug.start_with_request=yes env.php

define('SR', 48000);
define('RS', 64);

class Slope {
    var $state = 'OFF';
    var $currVal = 0;
    var $targetVal = 0;   //used for saturated ramping
    var $goalVal = 0;     //real goal.
    var $factor = 0;    //will be added on commit
}

class ADSFR {
    var $sLevel = 0.5;
    var $aFactor = 0;
    var $dFactor = 0;
    var $fFactor = 0;
    var $rFactor = 0;

    public function setTime($phase, $time) {
        switch ($phase) {
            case 'ATTACK':
                $this->aFactor = $this->calcDelta($time);
                break;
            case 'DECAY':
                $this->dFactor = $this->calcDelta($time);
                break;
            case 'FADE':
                $this->fFactor = $this->calcDelta($time);
                break;
            case 'RELEASE':
                $this->rFactor = $this->calcDelta($time);
                break;
        }
    }

    function calcDelta(float $time) {
        //run to calc factor based on time in mS..
        $totalSamples = $time * SR * 0.001;
        return (1 - exp(-RS / $totalSamples));
    }

    public function setLevel($phase, $level) {
        $this->sLevel = $level;
    }

    public function triggerSlope(Slope &$slope, $cmd) {
        //this is the public interface for the slope really..
        switch ($cmd) {
            case 'ON':
                $this->setSlopeState($slope, 'ATTACK');
                break;
            case 'OFF':
                $this->setSlopeState($slope, 'RELEASE');
                break;
            case 'RE-ON':
                //cancel any off and go back to fade. (pedal)
                $this->setSlopeState($slope, 'FADE');
                break;
            default:
                die('illegal state request from client');
        }
    }

    function setSlopeState(Slope &$slope, $state) {
        //called on commit or whenever. Private method.
        echo 'state-changing to ' . $state . PHP_EOL;
        switch ($state) {
            case 'ATTACK':
                $slope->state = 'ATTACK';
                $slope->goalVal = 1;
                $slope->targetVal = 1.3;
                $slope->factor = $this->aFactor;
                break;
            case 'DECAY':
                $slope->state = 'DECAY';
                $slope->goalVal = $this->sLevel;
                $slope->targetVal = $this->sLevel * 0.7;
                $slope->factor = $this->dFactor;
                break;
            case 'SUSTAIN':
                //really never a state is it?
                break;
            case 'FADE':
                $slope->state = 'FADE';
                $slope->goalVal = 0;
                $slope->targetVal =  $slope->currVal * -1.53;
                $slope->factor = $this->fFactor;
                break;
            case 'RELEASE':
                $slope->state = 'RELEASE';
                $slope->goalVal = 0;
                $slope->targetVal = $slope->currVal * -1.53;
                $slope->factor = $this->rFactor;
                break;
            case 'OFF':
                $slope->state = 'OFF';
                $slope->currVal = 0;
                $slope->goalVal = 0;
                $slope->targetVal = 0;
                $slope->factor = 0;
                break;
        }
    }

    function updateDelta(Slope &$slope) {
        if ($slope->state == 'ATTACK') {
            if ($slope->currVal > $slope->goalVal) {
                $this->stateChange($slope);
            }
        } else {
            if ($slope->currVal < $slope->goalVal) {
                $this->stateChange($slope);
            }
        }
        //calc delta
        $gap = $slope->targetVal - $slope->currVal;
        //no overflow protection
        $slope->currVal += $gap * $slope->factor;
        return $gap * $slope->factor * (1 / RS);
    }

    function stateChange(&$slope) {
        switch ($slope->state) {
            case 'ATTACK':
                $this->setSlopeState($slope, 'DECAY');
                break;
            case 'DECAY':
                $this->setSlopeState($slope, 'FADE');
                break;
            case 'FADE':
                $this->setSlopeState($slope, 'OFF');
                break;
            case 'RELEASE':
                $this->setSlopeState($slope, 'OFF');
                break;
        }
    }
}

$vca = new ADSFR();
$vca->setTime('ATTACK', 2);
$vca->setTime('DECAY', 8);
$vca->setTime('FADE', 2000);
$vca->setTime('RELEASE', 100);
$vca->setLevel('SUSTAIN', 0.5);


$mySlope = new Slope();

//ON:
$vca->triggerSlope($mySlope, 'ON');

for ($i = 0; $i < 15; $i++) {
    $easingStep = $vca->updateDelta($mySlope);
    for ($j = 0; $j < RS; $j++) {
        //minor step
    }
}

//OFF:
$vca->triggerSlope($mySlope, 'OFF');

for ($i = 0; $i < 60; $i++) {
    $easingStep = $vca->updateDelta($mySlope);
    for ($j = 0; $j < RS; $j++) {
        //minor step
    }
}
