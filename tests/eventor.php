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
$myEV2 = $myRack->loadEventor('retrigger',2);

require('../utils/wavWriter.php');
$ww = new WavWriter('eventor.wav',20000, $sr);
$timer = microtime(true);

//test 1 - re-trigger same note.
$mySub->setParam('VCA_SUSTAIN', 0.4);
$mySub->setParam('VCA_RELEASE', 200);

$myRack->parseMidi(0x90,72,120);
$ww->append($app->testRender(10));  
$myRack->parseMidi(0x80,72,0);
$ww->append($app->testRender(50));  

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$app->close();

?>
