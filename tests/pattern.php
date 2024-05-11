<?php

//test voice allocation, polyphony and LINEAR adsr
//no effects.
require('../app.php');
$sr = 44100; //something not workning when changing SR.
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subcult');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
$mySub->pushSettings();

//Format similar to mid-format but each note has timestamp, must be in order.
//For the moment settle with PPQN: 24, later adjust to 96 or higher.
$pattern = array(
    //start, len, note, vel
    array(0,0x94,65,34),
    array(20,0x80,65,64),

    array(24,0x90,70,100),
    array(44,0x80,70,64),

    array(48,0x90,80,94),
    array(56,0x80,80,64),
    array(60,0x90,78,94),
    array(63,0x80,78,64),
    array(64,0x90,77,94),
    array(67,0x80,77,64),

    array(72,0x90,85,64),    
    array(72,0x90,89,64),    
    array(90,0x80,85,64),    
    array(90,0x80,89,64),    
);

foreach($pattern as &$rec) {
    $rec[0] *= 4;
}
$myRack->loadPattern($pattern, 1);

require('../utils/wavWriter.php');
$ww = new WavWriter('pattern.wav',5000,$sr);
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
