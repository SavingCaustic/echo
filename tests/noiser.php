<?php
//test voice allocation, polyphony and LINEAR adsr
//no effects.
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.

require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'noiser');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');
require('wavWriter.php');
$ww = new WavWriter('noiser.wav',2000,44100 / SR_IF);
$timer = microtime(true);

$mySynth = $myRack->getSynthRef();
//dunno about the argument. All params should probably be int:s right?
for($i=0;$i<2000;$i++) {
  $ww->append($PE->testRender(3)); //40*1024 / 44100
}

echo 'Time: ' . (microtime(true) - $timer) . "\r\n";
$ww->close();
$PE->close();
?>
