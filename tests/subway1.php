<?php
//test voice allocation, polyphony and LINEAR adsr
//note that we're generating wav from RACK, not master.
require('../app.php');
$sr = 44100/2;
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subway');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
$myDelay = $myRack->loadEffect('delay');

require('../utils/wavWriter.php');
$ww = new WavWriter('subway1.wav',30000, $sr);
$timer = microtime(true);

//test 1 - re-trigger same note.
$mySub->setParam('VCA_SUSTAIN', 0.4);
$mySub->noteOn(69,120,50);	        //note,velocity,delaySamples (delay samples not impl.)
$ww->append($app->testRender(10));  
$mySub->noteOn(69,120,50);	        
$ww->append($app->testRender(10));  
$mySub->noteOff(69,120,50);
$ww->append($app->testRender(10));  

//test 2 - play another note
$mySub->noteOn(69,50,50);	        
$ww->append($app->testRender(10));  
$mySub->noteOn(73,50,50);	        
$ww->append($app->testRender(10));  
$mySub->noteOff(69,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$mySub->noteOff(73,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$ww->append($app->testRender(20));  

//test 3 - play chord. Distortion! 
$mySub->setParam('VCA_RELEASE', 1000);
$mySub->noteOn(60,100,50);
$mySub->noteOn(64,100,50);
$mySub->noteOn(67,100,50);
$mySub->noteOn(71,100,50);
$ww->append($app->testRender(10));  

$mySub->noteOff(60,20,50);
$mySub->noteOff(64,20,50);
$mySub->noteOff(67,20,50);
$mySub->noteOff(71,20,50);
$ww->append($app->testRender(50));  
$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$app->close();

?>
