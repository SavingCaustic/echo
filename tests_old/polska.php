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
$ww = new WavWriter('polska.wav',20000);
$timer = microtime(true);


//note: clock is 24ppqn, tick is 96 ppqn.

$pattern = array();
$strPattern = array(
    '123412341234',
    'x...........',
    '....x...x...'
);


$notes = array(0,60,62,63,71);
for($row=1;$row<sizeof($strPattern);$row++) {
    $pRow = $strPattern[$row];
    for($i=0;$i<12;$i++) {
        if ($pRow[$i] == 'x') {
            $pattern[] = [$i*24, 0x90, $notes[$row], 70];
            $pattern[] = [$i*24+4, 0x80, $notes[$row], 0];
        }
    }
}

//die(serialize($pattern));
//die('TIB: ' . $PE->ticksInBar);
$a = array_column($pattern,0);
array_multisort($a, SORT_ASC, $pattern);
//die(print_r($pattern));

$PE->setTempo(160,3,4);
//$PE->setVal('swing_16',0.5);
$PE->setVal('swing_8',0.0); //= 0.5
$PE->setVal('swing_16',0.0); //= 0.5
$PE->setVal('swing_1',0.4); //= 0.5

$myRack->loadPattern($pattern, 1,3,4);

//yep, good question - where should test-render be?
$ww->append($PE->testRender(0));
//$app->playMode('pattern');
$PE->play();
$ww->append($PE->testRender(100));
$PE->stop();    //pause not working!
$ww->append($PE->testRender(30));

$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$PE->close();   //should maybe be quit. 
