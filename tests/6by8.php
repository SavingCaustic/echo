<?php
require('testWriter.php');
$TW = new TestWriter(20000);

$PE = $TW->getPE();
$PE->rackSetup(1, 'beatnik');
$myRack = $PE->getRackRef(1);
$mySub = $myRack->getSynthRef();

//$myDelay = $myRack->loadEffect('delay');

$PE->setVal('time_sign','6/8');      //always quarter notes.
$PE->setVal('bpm',50);               //always quarter notes.

$PE->setVal('play_mode', 'pattern');
$PE->setVal('swing_level', 0.0);
$PE->setVal('swing_cycle', 24);    //in clocks. so 24 = 1/4 => 8th swing.

$notes = array();
$id = 1000;
$vel = array(120, 60, 60);
for ($i = 0; $i < 12; $i++) {
    $notes[] = array(
        'id' => $id,
        'tick' => $i * 48,      //eights
        'len' => 24,  
        'note' => 50, 
        'vel' => $vel[$i % 3]
    );
    $id++;
    if ($i % 6 == 0) {
        //add kick
        $notes[] = array('id'=>$id+100,'tick'=>$i*48,'len'=>24,'note'=>54,'vel'=>100);
    }
    if ($i % 6 == 2) {
        //add OH. note group mute not working yet.
        $notes[] = array('id'=>$id+200,'tick'=>$i*48+24,'len'=>24,'note'=>51,'vel'=>100);
    }
    if ($i % 6 == 5) {
        //add OH. note group mute not working yet. Note ERROR inte time that should be auto-corrected
        $notes[] = array('id'=>$id+300,'tick'=>$i*48+24,'len'=>24,'note'=>50,'vel'=>100);
    }
}
$pattern = array(
    'notes' => $notes,
    'barCount' => 1,
    'signNom' => 6,
    'signDenom' => 8,
    'grid' => 8
);
$json = json_encode($pattern,JSON_UNESCAPED_SLASHES);
$myRack->loadPatternFromJSON($json);   //what should be reset here? as we load?
$PE->hTapeController->respondToKey('PLAY');

$TW->render(300);

$PE->hTapeController->respondToKey('STOP');
$TW->render(5);

$TW->close();
