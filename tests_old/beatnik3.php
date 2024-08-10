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
$ww = new WavWriter('beatnik3.wav',20000);
$timer = microtime(true);


//note: clock is 24ppqn, tick is 96 ppqn.

$pattern = array();
for($i=0;$i<16;$i++) {
    if (in_array($i,explode(',','0,2,4,6,8,10,12,14'))) {
	//HH
        $pattern[] = [$i*24, 0x90, 50, 70 - ($i % 4)*10];
        $pattern[] = [$i*24 + 4, 0x80, 50, 0];
    }
    if (in_array($i,array(0,1,7,12))) {
        $pattern[] = [$i*24, 0x90, 52, rand(90,100)];
        $pattern[] = [$i*24 + 4, 0x80, 52, 0];    
    }
    if (in_array($i,array(2,10))) {
        $pattern[] = [$i*24, 0x90, 49, rand(90,100)];
        $pattern[] = [$i*24 + 4, 0x80, 49, 0];    
    }
    if (in_array($i,array(4,11,12,13))) {
        $pattern[] = [$i*24, 0x90, 53, rand(90,100)];
        $pattern[] = [$i*24 + 4, 0x80, 53, 0];    
    }
}

$a = array_column($pattern,0);
array_multisort($a, SORT_ASC, $pattern);
//die(print_r($pattern));

$PE->setTempo(76);
$myRack->loadPattern($pattern, 1);
$myRack->setSwing(48,0.5,true); //swing may also be negative!

//yep, good question - where should test-render be?
$ww->append($PE->testRender(0));
//$app->playMode('pattern');
$PE->play();
$ww->append($PE->testRender(300));
$PE->stop();    //pause not working!
$ww->append($PE->testRender(30));

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();   //should maybe be quit. 
