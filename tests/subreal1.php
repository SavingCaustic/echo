<?php
//test voice allocation, polyphony and LINEAR adsr
//note that we're generating wav from RACK, not master.
require('../app.php');
$sr = 44100;
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subreal');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
//$myDelay = $myRack->loadEffect('delay');
$myEV1 = $myRack->loadEventor('octaver',1);
//$myEV2 = $myRack->loadEventor('retrigger',2);

require('../utils/wavWriter.php');
$ww = new WavWriter('subreal1.wav',20000, $sr);
$timer = microtime(true);

//test 1 - re-trigger same note.
//$mySub->setParam('VCA_SUSTAIN', 0.4);
//$mySub->setParam('VCA_RELEASE', 0.2);

$myRack->parseMidi(0x90,72,120);
$ww->append($app->testRender(10));  
$myRack->parseMidi(0x80,72,0);
$ww->append($app->testRender(50));  
$myRack->parseMidi(0x80,72,120);
$myRack->parseMidi(0x90,72,120);
//$mySub->noteOn(69,120,50);	        
$ww->append($app->testRender(10));  
$myRack->parseMidi(0x80,72,120);
//$mySub->noteOff(69,120,50);
$ww->append($app->testRender(30));  

//test 2 - play another note
$mySub->noteOn(69,50,50);	        
$ww->append($app->testRender(10));  
$mySub->noteOn(73,50,50);	        
$ww->append($app->testRender(10));  
$mySub->noteOff(69,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$mySub->noteOff(73,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$ww->append($app->testRender(20));  

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$app->close();

?>
