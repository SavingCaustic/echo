<?php
//test voice allocation, polyphony and LINEAR adsr
//no effects.
require('../app.php');
$sr = 44100;
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'waveform');
$myRack = $app->getRackRef(1);

$mySynth = $myRack->getSynthRef();
//dunno about the argument. All params should probably be int:s right?
$mySynth->setParam('VOICES',25);
$mySynth->pushSettings();

require('../utils/wavWriter.php');
$ww = new WavWriter('bench.wav', 5000, $sr);
$timer = microtime(true);

$ww->append($app->testRender(44)); //40*1024 / 44100

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$app->close();
?>
