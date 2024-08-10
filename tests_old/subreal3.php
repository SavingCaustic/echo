<?php
//test voice allocation, polyphony and LFO RAMP
//note that we're generating wav from RACK, not master.
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'subreal');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

require('wavWriter.php');
$ww = new WavWriter('subreal3.wav',10000,44100 / SR_IF);
$timer = microtime(true);

//test 1 - re-trigger same note.
$mySub->setParam('VCA_SUSTAIN', 0.8);
$mySub->setParam('VCA_HOLD', 5000);
$mySub->setParam('VCA_RELEASE', 400);
$mySub->setParam('OSC_MIX', 0.5);
$mySub->setParam('OSC2_MODLEVEL', 0.9);
$mySub->setParam('OSC2_MODTYPE', 'FM');
$mySub->setParam('OSC2_OCT', -1);
$mySub->setParam('LFO1_SPEED',30);
$mySub->setParam('LFO1_DEPTH',2.5);
$mySub->setParam('LFO1_RAMP',1000);
$mySub->setParam('VCF_CUTOFF',4000);
$mySub->setParam('VCF_RESONANCE',0.5);

$myRack->parseMidi(0x90,72,120);
$ww->append($PE->testRender(20));  
$myRack->parseMidi(0x80,72,00);
$ww->append($PE->testRender(50));  
$myRack->parseMidi(0x90,72,120);
$ww->append($PE->testRender(100));  
$myRack->parseMidi(0x90,76,120);
$myRack->parseMidi(0x80,72,0);
$ww->append($PE->testRender(100));  
$myRack->parseMidi(0x80,76,0);
$ww->append($PE->testRender(20));  
$myRack->parseMidi(0x90,72,120);
$myRack->parseMidi(0x90,76,120);
$ww->append($PE->testRender(120));  
$myRack->parseMidi(0x80,72,0);
$myRack->parseMidi(0x80,76,0);
$ww->append($PE->testRender(20));  

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();

?>
