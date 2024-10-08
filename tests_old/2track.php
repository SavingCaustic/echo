<?php
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.
                                    //app and playerEngine really two different things. What to setup first?
require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'beatnik');        
$myRack = $PE->getRackRef(1);
$myBeat = $myRack->getSynthRef();

$PE->rackSetup(2,'subreal');        
$mySecondRack = $PE->getRackRef(2);
$mySub = $mySecondRack->getSynthRef();

//test 1 - re-trigger same note.
$mySub->setParam('VCA_SUSTAIN', 0.4);
$mySub->setParam('VCA_RELEASE', 200);
$mySub->setParam('OSC2_MODLEVEL', 0.9);
$mySub->setParam('OSC2_MODTYPE', 'FM');
$mySub->setParam('OSC2_OCT', 0);
$mySub->setParam('VCF_CUTOFF', 2000);
$mySub->setParam('VCF_RESONANCE', 0.8);

$mySub->pushAllParams();

$myDelay = $mySecondRack->loadEffect('delay');
$myDelay->setParam('TIME',0.17);
$myEV1 = $mySecondRack->loadEventor('octaver',1);

require('wavWriter.php');
$ww = new WavWriter('2track.wav',5000);
$timer = microtime(true);

//note: clock is 24ppqn, tick is 96 ppqn.
$pattern = array();
for($i=0;$i<16;$i++) {
    if ($i % 2 == 0) {
        $pattern[] = [$i*24, 0x90, 50, rand(50,100)];
        $pattern[] = [$i*24 + 4, 0x80, 50, 0];
    }
    if ($i % 4 == 0) {
        $pattern[] = [$i*24, 0x90, 48, rand(90,100)];
        $pattern[] = [$i*24 + 4, 0x80, 48, 0];    
    }
    if ($i == 0) {
        $pattern[] = [$i*24, 0x90, 55, 120];
        $pattern[] = [$i*24 + 4, 0x80, 55, 0];    
    }
    if ($i == 1) {
        $pattern[] = [$i*24, 0x90, 49, rand(50,100)];
        $pattern[] = [$i*24 + 4, 0x80, 49, 0];    
    }
    if ($i == 3) {
        $pattern[] = [$i*24, 0x90, 49, rand(50,100)];
        $pattern[] = [$i*24 + 4, 0x80, 49, 0];    
        $pattern[] = [$i*24, 0x90, 53, rand(50,100)];
        $pattern[] = [$i*24 + 4, 0x80, 53, 0];    
    }
    if ($i % 4 == 3) {
        $pattern[] = [$i*24, 0x90, 50, rand(50,100)];
        $pattern[] = [$i*24 + 4, 0x80, 50, 0];
        $pattern[] = [$i*24+12, 0x90, 50, rand(50,100)];
        $pattern[] = [$i*24+12 + 4, 0x80, 50, 0];
    }
    if ($i == 14) {
        $pattern[] = [$i*24, 0x90, 49, 127];
        $pattern[] = [$i*24 + 4, 0x80, 49, 0];    
    }
}

$a = array_column($pattern,0);
array_multisort($a, SORT_ASC, $pattern);
//die(print_r($pattern));

$PE->setTempo(80);
$myRack->loadPattern($pattern, 1);
$PE->setVal('swing_16',0.3);
//$myRack->setSwing(48,0.5,false); //swing may also be negative!

$p2 = array(
    array(0,0x90,60,120),
    array(0+20,0x80,60,0),
    array(48,0x90,60,120),
    array(48+20,0x80,60,0),
    array(96-24,0x90,63,120),
    array(96-24+20,0x80,63,0),
    array(96*3,0x90,55,100),
    array(96*3+20,0x80,55,0),
    array(96*3+48,0x90,58,100),
    array(96*3+48+20,0x80,58,0),
);

$mySecondRack->loadPattern($p2, 1);
//$mySecondRack->setSwing(48,0.5,false); //swing may also be negative!


//yep, good question - where should test-render be?
$ww->append($PE->testRender(0));
//$app->playMode('pattern');
$PE->play();
for($i=0;$i<150;$i++) {
  $ww->append($PE->testRender(2));
}
$PE->stop();    //pause not working!
$ww->append($PE->testRender(30));

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();   //should maybe be quit. 
