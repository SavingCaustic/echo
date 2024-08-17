<?php
require('testWriter.php');
$TW = new TestWriter(20000);

$PE = $TW->getPE();

$PE->rackSetup(1, 'beatnik');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

$strPattern = array(
    '1234123412341234',
    'x..x.x.x...x....',
    '....x.......x.x.',
    '.............x..',
    'xxxxxx.x.x......'
);
$pNotes = array();
$notes = array(0, 60, 62, 63, 71);
for ($row = 1; $row < sizeof($strPattern); $row++) {
    $pRow = $strPattern[$row];
    for ($i = 0; $i < 16; $i++) {
        if ($pRow[$i] == 'x') {
            $pNotes[] = array(
                'id' => $i + $row*16, 
                'tick' => $i * 48, 
                'note' => $notes[$row], 
                'len' => 24,
                'vel' => 70
            );
        }
    }
}


$PE->setNum('bpm',105);
$pattern = array(
    'notes' => $pNotes,
    'barCount' => 1,
    'signNom' => 4,
    'signDenom' => 4,
    'grid' => 16
);
$json = json_encode($pattern, JSON_UNESCAPED_SLASHES);

$myRack->loadPatternFromJSON($json, 1);
$PE->setNum('swing_level', 0.3);
$PE->setNum('swing_cycle', 12);    //in clocks. so 24 = 1/4 => 8th swing.

$myDelay = $myRack->loadEffect('delay');
$myDelay->setNum('FEEDBACK',0.1);
$myDelay->setNum('TIME',0.25);

$PE->hTapeController->respondToKey('PLAY');
//clock should run here, even though tick doesn't
$TW->render(215);
$myDelay->setNum('FEEDBACK',0.5);
$PE->hTapeController->respondToKey('STOP');
$TW->render(50);
$TW->close();
