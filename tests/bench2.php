<?php
//same as bench but changing sample-frequency. Should be faster to render.
require('../app.php');
$sr = 44100/1;
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'waveform');
$myRack = $app->getRackRef(1);
$mySynth = $myRack->getSynthRef();
$mySynth->setParam('VOICES',30);

require('../utils/wavWriter.php');
$ww = new WavWriter('bench2.wav',5000, $sr);
$timer = microtime(true);

$ww->append($app->testRender(44)); //344*128 => 44.1 samples => 1 sec.

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$app->close();
?>
