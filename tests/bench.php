<?php
//test voice allocation, polyphony and LINEAR adsr
//no effects.
echo "Testing 25 oscillators.. \r\n";
require('testWriter.php');
$TW = new TestWriter(20000);
$PE = $TW->getPE();

$PE->rackSetup(1,'waveform');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');

$mySynth = $myRack->getSynthRef();
//dunno about the argument. All params should probably be int:s right?
$mySynth->setParam('VOICES',25);
$mySynth->pushAllParams();

$TW->render(100); //40*1024 / 44100

$TW->close();
?>
