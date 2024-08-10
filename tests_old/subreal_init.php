<?php
//test voice allocation, polyphony and LINEAR adsr
//note that we're generating wav from RACK, not master.
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'subreal');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

require('wavWriter.php');
$ww = new WavWriter('subreal_init.wav',15000,44100 / SR_IF);
$timer = microtime(true);


$myRack->parseMidi(0x90,72,120);
$myRack->parseMidi(0x90,74,120);
$ww->append($PE->testRender(10));  
$myRack->parseMidi(0x80,72,0);
$myRack->parseMidi(0x80,74,0);
$ww->append($PE->testRender(50));  
$myRack->parseMidi(0x80,72,120);
$myRack->parseMidi(0x90,72,120);
//$mySub->noteOn(69,120,50);	        
$ww->append($PE->testRender(10));  
$myRack->parseMidi(0x80,72,120);
//$mySub->noteOff(69,120,50);
$ww->append($PE->testRender(30));  

//test 2 - play another note
$mySub->noteOn(69,50,50);	        
$ww->append($PE->testRender(10));  
$mySub->noteOn(73,50,50);	        
$ww->append($PE->testRender(10));  
$mySub->noteOff(69,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$mySub->noteOff(73,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$ww->append($PE->testRender(20));  

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();

?>
