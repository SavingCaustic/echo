<?php
echo "Testing eventor running without play command. \r\n";
require('testWriter.php');
$TW = new TestWriter(20000);

$PE = $TW->getPE();
$PE->rackSetup(1, 'beatnik');
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');

$timer = microtime(true);
$myRack->loadEventor('sixteener');
$PE->setVal('bpm', 105);
$PE->setVal('play_mode', 'pattern');
$PE->setVal('swing_level', 0.33);
$PE->setVal('swing_cycle', 12);    //in clocks. so 24 = 1/4 => 8th swing.
//what here. Buttons are being
$PE->hTapeController->respondToKey('STOP');
//clock should run here, even though tick doesn't
$TW->render(120);
$myRack->unloadEventor(1);
$TW->render(20);
$TW->close();
