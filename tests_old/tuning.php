<?php
//test voice allocation, polyphony and LINEAR adsr
//note that we're generating wav from RACK, not master.
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'subreal');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');
require('wavWriter.php');
$ww = new WavWriter('tuning.wav',15000,44100 / SR_IF);
$timer = microtime(true);

//test 1 - re-trigger same note.
$mySub->setParam('VCA_SUSTAIN', 0.4);
$mySub->setParam('VCF_CUTOFF', 12000);
$mySub->setParam('VCA_RELEASE', 200);
$mySub->setParam('VCA_HOLD', 10000);
$mySub->setParam('OSC2_MODLEVEL', 0.2);
$mySub->setParam('OSC2_MODTYPE', 'FM');
$mySub->setParam('OSC2_OCT', 1);

$myRack->parseMidi(0x90,80,100);
$ww->append($PE->testRender(300));  
$myRack->parseMidi(0x80,70,0);

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();

?>
