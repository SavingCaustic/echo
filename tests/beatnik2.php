<?php
echo "Testing pattern running on PLAY. With 2-bar looping. \r\n";
require('testWriter.php');
$TW = new TestWriter(20000, 48000, true);

$PE = $TW->getPE();
$PE->rackSetup(1, 'beatnik');
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');

$timer = microtime(true);
$PE->setNum('BPM', 88);
$PE->setStr('PLAY_MODE', 'pattern');
$PE->setNum('SWING_LEVEL', 0.60); //0.3
$PE->setNum('SWING_CYCLE', 24);    //in clocks. so 24 = 1/4 => 8th swing.

$notes = array();
$id = 1000;
$vel = array(70, 60, 60, 50, 70, 60, 60, 50);
//create a two-bar pattern for playing..
for ($i = 0; $i < 64; $i++) {
    $notes[] = array(
        'id' => $id,
        'tick' => $i * 30,  //PPQN=240. so this is 32-notes
        'len' => 12,        //WE SHOULD MIGRATE TO 240 PPQN!
        'note' => 50,
        'vel' => $vel[$i % 8]
    );
    if ($i % 16 == 0) {
        $notes[] = array('id' => $id + 200, 'tick' => $i * 60, 'len' => 12, 'note' => 52, 'vel' => 100);
    }
    if ($i == 30) {
        $notes[] = array('id' => $id + 200, 'tick' => $i * 60, 'len' => 12, 'note' => 51, 'vel' => 100);
    }
    $id++;
}

$pattern = array(
    'notes' => $notes,
    'barCount' => 2,
    'signNom' => 4,
    'signDenom' => 4,
    'grid' => 16
);
$json = json_encode($pattern, JSON_UNESCAPED_SLASHES);
$myRack->loadPatternFromJSON($json);   //what should be reset here? as we load?
$PE->hTapeController->respondToKey('PLAY');
$TW->render(300); //90 * 1024

$PE->hTapeController->respondToKey('STOP');
$TW->render(10);

$TW->close();
