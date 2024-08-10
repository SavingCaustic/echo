<?php
require('testWriter.php');
$TW = new TestWriter(10000);

$PE = $TW->getPE();

$PE->rackSetup(1,'noiser');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');

$mySynth = $myRack->getSynthRef();
//dunno about the argument. All params should probably be int:s right?
$TW->render(2000);

$TW->close();

