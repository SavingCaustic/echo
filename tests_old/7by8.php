<?php
define('SR_IF',1);                  //sample-rate inverse factor. 2 for 22050Hz, 4 for 11025.
                                    //app and playerEngine really two different things. What to setup first?
require('../src/core/playerEngine.php');
$PE = new PlayerEngine();           //it doesn't have to autostart really..
$PE->rackSetup(1,'beatnik');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');
require('wavWriter.php');
$ww = new WavWriter('7by8.wav',20000);
$timer = microtime(true);


//note: clock is 24ppqn, tick is 96 ppqn.

$pattern = array();
$strPattern = array(
    '12345671234567',
    'x......x......',
    '...x.x....x.x.',
    'xx..x..xx..x..',
    'xxxxxx.x.x....'
);
$notes = array(0,60,62,63,71);
for($row=1;$row<sizeof($strPattern);$row++) {
    $pRow = $strPattern[$row];
    for($i=0;$i<14;$i++) {
        if ($pRow[$i] == 'x') {
            $pattern[] = [$i*24, 0x90, $notes[$row], 70];
            $pattern[] = [$i*24+4, 0x80, $notes[$row], 0];
        }
    }
}

//die(serialize($pattern));

$a = array_column($pattern,0);
array_multisort($a, SORT_ASC, $pattern);
//die(print_r($pattern));

//wrong tempo here, should be much slower..
$PE->setTempo(85,7,8);
$myRack->loadPattern($pattern, 1, 7, 8);    //yeah how many? We don't say anything about resolution..
//$myRack->setSwing(48,0.07,true); //swing may also be negative!

//yep, good question - where should test-render be?
$ww->append($PE->testRender(0));
//$app->playMode('pattern');
$PE->play();
$ww->append($PE->testRender(300));
$PE->stop();    //pause not working!
$ww->append($PE->testRender(30));

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();   //should maybe be quit. 
