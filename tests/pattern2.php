<?php

//can we get swing to work?
require('../app.php');
$sr = 44100; //something not workning when changing SR.
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subcult');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
$mySub->setParam('VCA_RELEASE',10);
$mySub->pushSettings();

//Format similar to mid-format but each note has timestamp, must be in order.
//For the moment settle with PPQN: 24, later adjust to 96 or higher.
$pattern = array();
for($i=0;$i<16;$i++) {
    $pattern[] = [$i*24, 0x90, 69, 80];
    $pattern[] = [$i*24 + 8, 0x80, 69, 0];
}

$myRack->loadPattern($pattern, 1);
$myRack->setSwing(48,0.5);

require('../utils/wavWriter.php');
$ww = new WavWriter('pattern2.wav',5000,$sr);
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
