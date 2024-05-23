<?php
//test voice allocation, polyphony and LINEAR adsr
//note that we're generating wav from RACK, not master.
require('../app.php');
$sr = 44100;
$app = new App($sr);               //pass something that can't be changed?
$app->init();
$app->rackSetup(1,'beatnik');
$myRack = $app->getRackRef(1);
$mySub = $myRack->getSynthRef();
//$myDelay = $myRack->loadEffect('delay');
require('../utils/wavWriter.php');
$ww = new WavWriter('beatnik1.wav',20000, $sr);
$timer = microtime(true);


//note: clock is 24ppqn, tick is 96 ppqn.
$pattern = array();
for($i=0;$i<32;$i++) {
    switch ($i % 8) {
        case 0:
            $pattern[] = [$i*4, 0x90, 48, 120];
            $pattern[] = [$i*4 + 4, 0x80, 48, 0];
            break;
        case 1:
        case 2:
        case 3:
            $pattern[] = [$i*4, 0x90, 50, 40];
            $pattern[] = [$i*4 + 4, 0x80, 50, 0];
            break;
        case 4:
            $pattern[] = [$i*4, 0x90, 49, 80];
            $pattern[] = [$i*4 + 4, 0x80, 49, 0];
            break;
        case 5:
        case 6:
        case 7:
            $pattern[] = [$i*4, 0x90, 50, 40];
            $pattern[] = [$i*4 + 4, 0x80, 50, 0];
            break;
    }
    //$pattern[] = [$i*4, 0x90, 50, 20];
    //$pattern[] = [$i*4 + 4, 0x80, 50, 0];
}

$a = array_column($pattern,0);
array_multisort($a, SORT_ASC, $pattern);
//die(serialize($pattern));
//increase to 96PPQN
foreach($pattern as &$rec) {
    $rec[0] *= 8;
}

$myRack->loadPattern($pattern, 2);
$myRack->setSwing(48,0.4,true && false); //swing may also be negative!

$ww->append($app->testRender(0));
//$app->playMode('pattern');
$app->playerEngine->play();
$ww->append($app->testRender(160));


$ww->close();

echo 'Time: ' . (microtime(true) - $timer);
$app->close();

?>
