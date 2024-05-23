<?php

//test voice allocation, polyphony and LINEAR adsr
//no effects.
require('../app.php');
$sr = 44100; //something not workning when changing SR.
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subreal');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
$mySub->pushSettings();
//$myEvt = $myRack->loadEventor('octaver',1);
$myEvt = $myRack->loadEventor('retrigger',1);

//note: clock is 24ppqn, tick is 96 ppqn.
$pattern = array(
    //tick, cmd, note, vel
    array(0,0x90,75,104),
    array(40,0x80,75,64),

    array(46,0x90,80,94),
    array(56,0x80,80,64),
    array(58,0x90,78,94),
    array(63,0x80,78,64),
    array(60,0x90,77,94),
    array(67,0x80,77,64),

    array(72,0x90,73,64),    
    array(72,0x90,77,64),    
    array(90,0x80,73,64),    
    array(90,0x80,77,64),    
);

//adjust timing to PPQN = 96
foreach($pattern as &$rec) {
    $rec[0] *= 4;
}
$myRack->loadPattern($pattern, 1);

require('../utils/wavWriter.php');
$ww = new WavWriter('pattern.wav',15000,$sr);
$timer = microtime(true);
//some silence
//$ww->append($myRack->render(256));  
$ww->append($app->testRender(0));
//$app->playMode('pattern');
$app->playerEngine->play();
$ww->append($app->testRender(160));
//die('asdf' . serialize($app->racks[1]->rackTick));

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$app->close();
?>
