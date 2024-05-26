<?php
//just testing to read a wav-file. 
//This is not what we want in the drum-machine though..

define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'wavplayer');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$myDelay = $myRack->loadEffect('delay');

//$myDelay = $myRack->loadEffect('delay');
require('../utils/wavWriter.php');
$ww = new WavWriter('wavplayer.wav',15000,44100 / SR_IF);
$timer = microtime(true);

$myRack->parseMidi(0x90,60,0);

$ww->append($PE->testRender(20));

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$PE->close();
?>
