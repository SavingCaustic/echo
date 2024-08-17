<?php
echo "Testing pattern running on PLAY. With 2-bar looping. \r\n";
require('testWriter.php');
$TW = new TestWriter(20000);

$PE = $TW->getPE();
$PE->rackSetup(1, 'beatnik');
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');

$timer = microtime(true);
$PE->setNum('BPM', 120);
$PE->setStr('PLAY_MODE', 'pattern');
$PE->setNum('SWING_LEVEL', 0.3);
$PE->setNum('SWING_CYCLE', 12);    //in clocks. so 24 = 1/4 => 8th swing.

$notes = array();
$id = 1000;
$vel = array(120, 60, 60, 20);
//create a two-bar pattern for playing..
for ($i = 0; $i < 32; $i++) {
    $notes[] = array(
        'id' => $id,
        'tick' => $i * 48,  //PPQN=192. so this is 16-notes
        'len' => 24,
        'note' => 50,
        'vel' => $vel[$i % 4]
    );
    if ($i % 8 == 0) {
        $notes[] = array('id' => $id + 200, 'tick' => $i * 48, 'len' => 24, 'note' => 52, 'vel' => 100);
    }
    if ($i == 31) {
        $notes[] = array('id' => $id + 200, 'tick' => $i * 48, 'len' => 24, 'note' => 51, 'vel' => 100);
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
