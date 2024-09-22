<?php
require('testWriter.php');
$TW = new TestWriter(10000,48000,true);

$PE = $TW->getPE();

$PE->rackSetup(1,'subreal');        //dunno really why the test-scripts would need the app? skip that.
$myRack = $PE->getRackRef(1);
    /**
     * @var SynthInterface                  //fixes syntax in VS Code
     */
$mySub = $myRack->getSynthRef();
//$myEV1 = $myRack->loadEventor('octaver',1);

//$myDelay = $myRack->loadEffect('delay');

//test 1 - re-trigger same note.
$mySub->setNum('VCA_ATTACK', 1);
$mySub->setNum('VCA_SUSTAIN', 0.9);
$mySub->setNum('VCA_RELEASE', 1400);
$mySub->setNum('OSC2_MODLEVEL', 0.5);
$mySub->setStr('OSC2_MODTYPE', 'NONE');
$mySub->setNum('OSC2_OCT', 1);
$mySub->setStr('OSC1_WF', 'SQUARE');
$mySub->setNum('VCF_ATTACK', 150);
$mySub->setNum('LFO1_RAMP', 1000);
$mySub->setNum('LFO1_DEPTH', 0.8);


$myRack->parseMidi(0x90,63,120);
//$myRack->parseMidi(0x90,74,120);
$TW->render(50);
$myRack->parseMidi(0x80,63,0);
$TW->render(40);
/*
//$myRack->parseMidi(0x80,72,0);
$myRack->parseMidi(0x80,85,0);
$TW->render(50);
$myRack->parseMidi(0x90,60,120);
//$mySub->noteOn(69,120,50);	        
$TW->render(50);
$myRack->parseMidi(0x80,60,0);
//$mySub->noteOff(69,120,50);
$TW->render(20);

//test 2 - play another note
$mySub->noteOn(44,50,50);	        
$TW->render(30);
//$mySub->noteOn(73,50,50);	        
$TW->render(10);
$mySub->noteOff(44,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
//$mySub->noteOff(73,20,50);	        //note,velocity,delaySamples (delay samples not impl.)
$TW->render(40);
*/
$TW->close();

