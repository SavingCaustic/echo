<?php
declare(strict_types=1);

//simplifed time rendering. No delayed rendering - render audio and for each,
//increase playhead with 128/44100 sec.

$drumNotes = array(
  array(0,6,60,64),
  array(24,6,62,64),
  array(48,6,60,64),
  array(72,6,62,64),
  array(0+96,6,60,64),
  array(24+96,6,62,64),
  array(48+96,6,60,64),
  array(72+96,6,62,64),
  array(0,6,63,64),
  array(12,6,63,64),
  array(24,6,63,64),
  array(36,6,63,64),
  array(48,6,63,64),
  array(60,12,64,64),
  array(84,6,63,64),
  //also the sequence may contain pre-count notes as negative times
  array(-24,6,67,64)
);

$billyJean = array(
  array(0,6,60,64),
  array(12,6,55,64),
  array(24,6,58,64),
  array(36,6,62,64),
  array(48,6,60,64),
  array(60,6,58,64),
  array(72,6,53,64),
  array(84,6,54,64)
);
$notes = $billyJean;

//on any pattern edit the array must be re-sorted on start-value.
array_multisort(array_column($notes,0),$notes);
//die(serialize($notes));

//now if pattern is to be played skip and -notes, if in song book them.


class PatternPlayer {
  //does the time-signature matter? Yes - so we know when to start to look in next pattern
  
  var $grid;
  var $gridPtr;
  var $ticksInGrid;
  var $currentTick; //not needed here right?
  var $idleRenders; //-1 forever

  function __construct() {
  }

  function loadPattern($data, $ticksInGrid = 96) {
    $this->grid = $data;
    $this->ticksInGrid = 96;
    $this->gridPtr = -1;
  }

  function startPattern() {
    $this->currentTick = 0;
  }

  function calcIdleTicks() {
    //calc how many idle renders for this pattern, based on current tick and next event.
    if (sizeof($this->grid) > ($this->gridPtr+1)) {
      $nextTick = $grid[$this->gridPtr][0];
      return $nextTick - $this->currentTick;
    }    
  }

  function loadPrePattern() {
    //
  }

  function checkIfAnythingToProcess() {
    //tick has been updated, anybody here?
  }

  function calcIdleRenders() {
    //based on current tick, and what's in next event - calc idleRenders
    $ticksPerSec = 48;
    $bufferSize = 256;
    $sampleRate = 44100;
    //    
  }

  
}


