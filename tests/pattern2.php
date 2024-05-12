<?php

//can we get swing to work?
require('../app.php');
$sr = 44100; //something not workning when changing SR.
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subcult');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
$mySub->setParam('VCA_DECAY',20);
$mySub->setParam('VCA_SUSTAIN',0.3);
$mySub->setParam('VCA_RELEASE',10);
$mySub->pushSettings();

//Format similar to mid-format but each note has timestamp, must be in order.
//For the moment settle with PPQN: 24, later adjust to 96 or higher.
$pattern = array();
for($i=0;$i<16;$i++) {
    switch ($i % 4) {
        case 0:
            $pattern[] = [$i*24, 0x90, 69, 120];
            $pattern[] = [$i*24 + 8, 0x80, 69, 0];
            break;
        case 2:
            //add extra..
            $pattern[] = [$i*24+12, 0x90, 73, 80];
            $pattern[] = [$i*24+12 + 8, 0x80, 73, 0];
            //don't uncomment break;
        default:
            $pattern[] = [$i*24, 0x90, 69, 80];
            $pattern[] = [$i*24 + 8, 0x80, 69, 0];
            break;
    }
}

$a = array_column($pattern,0);
array_multisort($a, SORT_ASC, $pattern);

$myRack->loadPattern($pattern, 1);
$myRack->setSwing(48,0.4,true); //swing may also be negative!

require('../utils/wavWriter.php');
$ww = new WavWriter('pattern2.wav',20000,$sr);
$timer = microtime(true);
//some silence
//$ww->append($myRack->render(256));  
$ww->append($app->testRender(10));
//$app->playMode('pattern');
$app->masterPlayer->setTempo(90);
$app->masterPlayer->play();
$ww->append($app->testRender(90));

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$app->close();
?>
