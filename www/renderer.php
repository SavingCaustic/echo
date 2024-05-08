<?php
//create wav and output as file, maybe later to browser?
require('../utils/wavWriter.php');

session_start();

class WaveRenderer {
    var $app;  
    var $WW;
    var $rack;
    var $synth;

    function __construct() {
        //load app
        require('../app.php');
        //settings anyone?
        $this->app = new App();
        $this->app->init();
        $this->WW = new WavWriter('renderer.wav',11000);
    }

    function loadSynth() {
        //we need a rack?
    	$synth = $_SESSION['synth'];
        $this->app->rackSetup(1,$synth); //'subsynth');
        $this->rack = $this->app->getRackRef(1);
        $this->synth = $this->rack->getSynthRef();
    }

    function loadParameters() {
        foreach($_SESSION as $key=>$val) {
            if (strtoupper($key) == $key) {
                $this->synth->setParam($key, $val);
            }
        }
    }

    function parseSequence() {
        $sequence = $_SESSION['sequence']; //'69x10,Px10,69+72+76x10,Px10';
        $steps = explode(',',$sequence);
        foreach($steps as $step) {
            $args = explode('x',$step);
            $notes = explode('+',$args[0]);
            if ($notes[0] == 'P') $notes = array();
            foreach($notes as $note) {
                $this->synth->noteOn($note,80);
            }
            $len = $args[1];
            if ($len > 40) $len = 40;
            //die('asdf ' . $len);
            $this->WW->append($this->app->testRender($len));
            foreach($notes as $note) {
                $this->synth->noteOff($note,80);
            }
        }
        //auto note off..
    }

    function saveFile() {
        $this->WW->close();
        $this->app->close();
    }
}

$WR = new WaveRenderer();
$WR->loadSynth();
//$WR->loadParameters();
$WR->parseSequence();
$WR->saveFile();

//header('Content-Type: audio/wav');
//echo file_get_contents('renderer.wav');
