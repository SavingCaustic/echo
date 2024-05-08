<?php

//test voice allocation, polyphony and LINEAR adsr
//no effects.
require('../app.php');
$sr = 44100/2; //something not workning when changing SR.
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subsynth');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
$mySub->pushSettings();

//Format similar to mid-format but each note has timestamp, must be in order.
//For the moment settle with PPQN: 24, later adjust to 96 or higher.
$sequence = array(
    //start, len, note, vel
    array(0,90,65,34),
    array(20,80,65,64),

    array(24,90,70,34),
    array(44,80,70,64),

    array(48,90,80,94),
    array(56,80,80,64),
    array(60,90,78,94),
    array(63,80,78,64),
    array(64,90,77,94),
    array(67,80,77,64),

    array(72,90,85,64),    
    array(72,90,89,64),    
    array(90,80,85,64),    
    array(90,80,89,64),    
);

$myRack->loadPattern($sequence, 1);

require('../utils/wavWriter.php');
$ww = new WavWriter('sequencer.wav',5000,$sr);
$timer = microtime(true);
//some silence
//$ww->append($myRack->render(256));  
$ww->append($app->testRender(10));
//$app->playMode('pattern');
$app->masterPlayer->play();
$ww->append($app->testRender(200));

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$app->close();
?>
