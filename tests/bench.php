<?php
//test voice allocation, polyphony and LINEAR adsr
//no effects.
define('SR_IF',2);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'waveform');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');
require('wavWriter.php');
$ww = new WavWriter('bench.wav',5000,44100 / SR_IF);
$timer = microtime(true);

$mySynth = $myRack->getSynthRef();
//dunno about the argument. All params should probably be int:s right?
$mySynth->setParam('VOICES',25);
$mySynth->pushAllParams();

$ww->append($PE->testRender(144)); //40*1024 / 44100

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$PE->close();
?>
