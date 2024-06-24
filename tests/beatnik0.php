<?php
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.
                                    //app and playerEngine really two different things. What to setup first?
require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'beatnik');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');
require('wavWriter.php');
$ww = new WavWriter('beatnik0.wav',20000);
$timer = microtime(true);

//note: clock is 24ppqn, tick is 96 ppqn.
$pattern = array();
for($i=0;$i<8;$i++) {
    $vel = ($i % 16 == 0) ? 127 : 60;
    $pattern[] = [$i*48, 0x90, 50, $vel];
    $pattern[] = [$i*48 + 8, 0x80, 50, 0];
}

$PE->setTempo(120);
$myRack->loadPattern($pattern, 1);
$myRack->setSwing(96,0,false); //swing may also be negative!

//yep, good question - where should test-render be?
$ww->append($PE->testRender(0));
//$app->playMode('pattern');
$PE->play();
$ww->append($PE->testRender(100)); //90 * 1024
$PE->stop();
$ww->append($PE->testRender(10));

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();   //should maybe be quit. 
