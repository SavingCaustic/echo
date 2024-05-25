<?php
//test voice allocation, polyphony and LINEAR adsr
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'subreal');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();
$myEV1 = $myRack->loadEventor('octaver',1);

require('../utils/wavWriter.php');
$ww = new WavWriter('subreal2.wav',20000);

$mySub->setParam('VCA_ATTACK', 20);

//test 1 - re-trigger same note. Note these don't go through eventors!
$mySub->noteOn(69,50,50);	        //note,velocity,delaySamples (delay samples not impl.)
$ww->append($PE->testRender(16));
$mySub->noteOn(69,60,50);	        
$ww->append($PE->testRender(5));
$mySub->noteOff(69,70,50);
$ww->append($PE->testRender(64));

$mySub->setParam('VCA_ATTACK', 3);
$mySub->setParam('VCF_ATTACK', 30);
$mySub->setParam('VCF_SUSTAIN', 0.4);
$mySub->setParam('VCF_CUTOFF', 4000);
$mySub->setParam('VCF_RESONANCE', 0.5);
$myRack->parseMidi(0x90,69,50);
$ww->append($PE->testRender(24));
$myRack->parseMidi(0x80,69,120);
$ww->append($PE->testRender(24));


$ww->close();
$PE->close();
?>
