<?php
declare(strict_types=1);

//this php file mocks the compiled binary, therefore outside the src-directory
//it's just a room for the audio-engine and UI-stuff to live in.

require('src/core/dspCore.php');
require('src/core/rack.php');
require('src/core/playerEngine.php');

class App {
    var $racks;
    var $messageQue;
    var $playerEngine;
    var $rackCount;
    var $dspCore;
    var $wave;      //ever used? why not wav2file on synth? 

    function __construct($sampleRate = 44100, $bufferSize = 1024) {
        $masterRenderSize = $bufferSize * ($sampleRate / 44100);
        //to get an accurate clock, rackRenderSize is relative to sampleRate, not bufferSize.
        $rackRenderSize = $sampleRate / 44100 * 64;
        $masterTune = 440;    //Hz
        $this->dspCore = new DspCore($sampleRate, $masterTune, $rackRenderSize, $masterRenderSize);
        $this->playerEngine = new PlayerEngine($this);
        $this->playerEngine->setTempo(120);
        $this->playerEngine->reset();
        //to be evaluated..
        $this->rackCount = 14;
        $this->dspCore->appDir = __DIR__;
    }
    function init() {
        //setup (t)racks
        for($i=0;$i<$this->rackCount;$i++) {
            $this->racks[$i] = null;
        }
    }

    function main() {
        //there is no main since this isn't closed loop. hmm. save it for later.
        //it would be nice if c++ could run tests without using main().
    }

    function rackSetup(int $rackIdx, string $synth) {
        //in c++, not really sure in how to allocate objects and best practice of controlling their lifetime.
        if (!is_null($this->racks[$rackIdx])) {
            die('hey, rack ' . $rackIdx . ' is occupied');
        }
        $this->racks[$rackIdx] = new Rack($rackIdx, $this->dspCore);
        $r = &$this->racks[$rackIdx];
        $r->loadSynth($synth);
    }

    function getRackRef(int $rackIdx) : Rack {
        return $this->racks[$rackIdx];
    }

    //some difference here between synthBuffer and audiooutBuffer.

    function testRender($blocks = 128) {
        //this is like main() for tests. Returns a wave of floats that could be converted to wav.
        //note this signal should be stereo.
        $waveOut = array();
        for($i=0;$i<$blocks;$i++) {
            $wave = $this->playerEngine->renderNextBlock($i);
            $waveOut = array_merge($waveOut, $wave);
        }
        return $waveOut;
    }
    
    function close() {}
}

