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
$PE->setNum('BPM', 105);
$PE->setStr('PLAY_MODE', 'pattern');
$PE->setNum('SWING_LEVEL', 0.1);
$PE->setNum('SWING_CYCLE', 12);    //in clocks. so 24 = 1/4 => 8th swing.
//what here. Buttons are being
$PE->hTapeController->respondToKey('STOP');
//clock should run here, even though tick doesn't
$TW->render(120);
$myRack->unloadEventor(1);
$TW->render(20);
$TW->close();
