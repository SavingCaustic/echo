<?php
//just testing to read a wav-file. 
//This is not what we want in the drum-machine though..

require('../app.php');
$sr = 44100;
$app = new App();               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'wavplayer');
$myRack = $app->getRackRef(1);
$mySynth = $myRack->getSynthRef();
$myDelay = $myRack->loadEffect('delay');

$mySynth->pushSettings();
$mySynth->noteOn(60,90,0);

require('../utils/wavWriter.php');
$ww = new WavWriter('wavplayer.wav',20000);
$timer = microtime(true);

$ww->append($app->testRender(44));
$mySynth->noteOff(60,120,0);

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$app->close();
?>
