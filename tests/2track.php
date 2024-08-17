<?php
require('testWriter.php');
$TW = new TestWriter(5000);

$PE = $TW->getPE();

$PE->setNum('bpm', 105);
$PE->setNum('song_mode', 0);
$PE->setNum('swing_level', 0.3);
$PE->setNum('swing_cycle', 12);    //in clocks. so 24 = 1/4 => 8th swing.

$PE->rackSetup(1, 'beatnik');
$myRack = $PE->getRackRef(1);
$myBeat = $myRack->getSynthRef();


$PE->rackSetup(2, 'subreal');
$myRack2 = $PE->getRackRef(2);

/**
* @var SubrealModel                  //fixes syntax in VS Code
*/
$mySub = $myRack2->getSynthRef();

//test 1 - re-trigger same note.
$mySub->setNum('VCA_ATTACK', 0);
$mySub->setNum('VCA_SUSTAIN', 0.8);
$mySub->setNum('VCA_RELEASE', 200);

$mySub->setNum('OSC2_MODLEVEL', 0.4);
$mySub->setStr('OSC2_MODTYPE', 'FM');
$mySub->setNum('OSC2_OCT', -1);
$mySub->setNum('VCF_CUTOFF', 8000);
$mySub->setNum('VCF_RESONANCE', 0.8);
$mySub->pushAllParams();
$myEV1 = $myRack2->loadEventor('octaver', 1);

$PE->rackSetup(3, 'subreal');
$myRack3 = $PE->getRackRef(3);
/**
* @var SubrealModel                  //fixes syntax in VS Code
*/
$mySub2 = $myRack3->getSynthRef();

/*
//$myDelay = $mySecondRack->loadEffect('delay');
//$myDelay->setParam('TIME',0.17);
*/
$p1 = array();
for ($i = 0; $i < 16; $i++) {
    $p1[$i] = array(
        'id' => $i,
        'tick' => $i * 48,
        'note' => $i + 46,
        'len' => 24,
        'vel' => rand(50, 100)
    );
}
$pattern = array(
    'notes' => $p1,
    'barCount' => 1,
    'signNom' => 4,
    'signDenom' => 4,
    'grid' => 16
);
$json = json_encode($pattern, JSON_UNESCAPED_SLASHES);
//die(print_r($json));
$myRack->loadPatternFromJSON($json);   //what should be reset here? as we load?
$myRack2->loadPatternFromJSON($json);   //what should be reset here? as we load?

$notes = array();
for ($j = 0; $j < 3; $j++) {
    for ($i = 0; $i < 3; $i++) {
        $notes[] = array(
            'id' => $i+($j*5+1),
            'tick' => 0 + 48*6*$j,
            'note' => 78 + $i * 7 - $j,
            'len' => 250,
            'vel' => 80
        );
    }
}
$pattern['notes'] = $notes;
$pattern['barCount'] = 2;
$json = json_encode($pattern, JSON_UNESCAPED_SLASHES);
$myRack3->loadPatternFromJSON($json);   //what should be reset here? as we load?

$mySub2->setNum('LFO1_DEPTH', 3);
$mySub2->setNum('VCA_RELEASE', 200);
$mySub2->setNum('OSC2_MODLEVEL', 0.4);
$mySub2->setStr('OSC2_MODTYPE', 'FM');
$mySub2->setNum('OSC2_OCT', 0);
$mySub2->setNum('VCA_ATTACK', 0);
$mySub2->setNum('VCA_SUSTAIN', 1);
$mySub2->setNum('VCA_RELEASE', 400);

$mySub2->setNum('VCF_ATTACK', 300);
$mySub2->setNum('VCA_RELEASE', 1500);
$mySub2->setNum('VCF_CUTOFF', 6000);
$mySub2->setNum('VCF_RESONANCE', 0.8);

$TW->render(0);
//$app->playMode('pattern');
$PE->hTapeController->respondToKey('PLAY');
$TW->render(300);
$PE->hTapeController->respondToKey('STOP');
$TW->render(30);

$TW->close();
