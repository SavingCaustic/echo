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
$ww = new WavWriter('beatnik1.wav',20000);
$timer = microtime(true);


//note: clock is 24ppqn, tick is 96 ppqn.
$pattern = array();
for($i=0;$i<16;$i++) {
    switch ($i % 8) {
        case 0:
            $pattern[] = [$i*24, 0x90, 48, 120];
            //$pattern[] = [$i*24, 0x90, 53, 40];
            $pattern[] = [$i*24 + 4, 0x80, 48, 0];
            //$pattern[] = [$i*24 + 4, 0x80, 53, 0];
            break;
        case 4:
            $pattern[] = [$i*24, 0x90, 49, 120];
            $pattern[] = [$i*24 + 4, 0x80, 49, 0];
            break;
        default:
            $pattern[] = [$i*24, 0x90, 49, 40];
            $pattern[] = [$i*24 + 4, 0x80, 49, 0];
            //$pattern[] = [$i*24 - 12, 0x90, 49, 40];
            //$pattern[] = [$i*24 - 12 + 4, 0x80, 49, 0];
            break;
    }
    //buggy - this doesn't work but should..
    //$pattern[] = [$i*24, 0x90, 50, 20];
    //$pattern[] = [$i*24, 0x80, 50, 0];
}

$a = array_column($pattern,0);
array_multisort($a, SORT_ASC, $pattern);
//die(serialize($pattern));
//increase to 96PPQN

$PE->setTempo(105);
$myRack->loadPattern($pattern, 1);
$myRack->setSwing(48,0.3,true); //swing may also be negative!

//yep, good question - where should test-render be?
$ww->append($PE->testRender(0));
//$app->playMode('pattern');
$PE->play();
$ww->append($PE->testRender(100));
$PE->stop();    //pause not working!
$ww->append($PE->testRender(30));

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();   //should maybe be quit. 
