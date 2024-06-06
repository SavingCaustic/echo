<?php
//test voice allocation, polyphony and LINEAR adsr
//note that we're generating wav from RACK, not master.
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

//the timing of retrigger is incorrect, as the triggertime  gets doubled
require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'subreal');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();
$myEV1 = $myRack->loadEventor('octaver',1);
$myEV2 = $myRack->loadEventor('retrigger',2);

//$myDelay = $myRack->loadEffect('delay');
require('../utils/wavWriter.php');
$ww = new WavWriter('eventor.wav',5000,44100 / SR_IF);
$timer = microtime(true);

//test 1 - re-trigger same note.
$mySub->setParam('VCA_SUSTAIN', 0.4);
$mySub->setParam('VCA_RELEASE', 200);

$myRack->parseMidi(0x90,72,120);
$ww->append($PE->testRender(50));  
$myRack->parseMidi(0x80,72,0);
$ww->append($PE->testRender(50));  

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();

?>
