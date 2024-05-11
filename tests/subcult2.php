<?php
//test voice allocation, polyphony and LINEAR adsr

require('../app.php');
$sr = 22050;
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'subcult');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');

require('../utils/wavWriter.php');
$ww = new WavWriter('subcult.wav',20000,$sr);

$mySub->setParam('VCA_ATTACK', 20);

//test 1 - re-trigger same note.
$mySub->noteOn(69,50,50);	        //note,velocity,delaySamples (delay samples not impl.)
$ww->append($app->testRender(16));
$mySub->noteOn(69,60,50);	        
$ww->append($app->testRender(5));
$mySub->noteOff(69,70,50);
$ww->append($app->testRender(64));

//test 2 - play another note
$mySub->noteOn(69,50,50);	        
$ww->append($app->testRender(16));
$mySub->noteOn(73,50,50);	        
$ww->append($app->testRender(16));
$mySub->noteOff(69,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$mySub->noteOff(73,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$ww->append($app->testRender(32));

//test 3 - play chord. Distortion! 
$mySub->setParam('VCA_RELEASE', 5000);
$mySub->setParam('VCA_SUSTAIN', 0.8);
$mySub->setParam('VCA_DECAY', 1000);
$mySub->noteOn(60,120,50);
$mySub->noteOn(64,120,50);
$mySub->noteOn(67,120,50);
$mySub->noteOn(71,120,50);
$ww->append($app->testRender(4));
$mySub->noteOff(60,20,50);
$mySub->noteOff(64,20,50);
$mySub->noteOff(67,20,50);
$mySub->noteOff(71,20,50);
$ww->append($app->testRender(50));

//test 4 - super fast - set Attack to 5 on all
$mySub->setParam('VCA_ATTACK',5);
$mySub->setParam('VCA_DECAY',20);
$mySub->setParam('VCA_SUSTAIN',0.4);
$mySub->setParam('VCA_RELEASE',20);
$mySub->debug = true;

$mySub->noteOn(67,80,50);
$ww->append($app->testRender(5));
$mySub->noteOn(67,80,50);
$ww->append($app->testRender(5));
$mySub->noteOn(67,80,50);
$ww->append($app->testRender(5));
$mySub->noteOff(67,80,50);
$ww->append($app->testRender(40));
$ww->close();

$app->close();
?>
