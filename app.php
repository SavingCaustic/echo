<?php
declare(strict_types=1);

//this php file mocks the compiled binary, therefore outside the src-directory
//Test files DOES NOT NEED this file.

require('src/core/playerEngine.php');

class App {
    //i'm the shepherd sheepdog keeping it all together.
    //but maybe things gradually move to the playerEngine?
    var $playerEngine;
    var $wave;      //ever used? why not wav2file on synth? 

    function __construct($sampleRate = 44100, $bufferSize = 1024) {
        $masterRenderSize = $bufferSize * ($sampleRate / 44100);
        //to get an accurate clock, rackRenderSize is relative to sampleRate, not bufferSize.
        $rackRenderSize = $sampleRate / 44100 * 64;
        $masterTune = 440;    //Hz

        $this->playerEngine = new PlayerEngine($sampleRate, $masterTune, $rackRenderSize, $masterRenderSize);
        $this->playerEngine->setTempo(120);
        $this->playerEngine->reset();
    }

    function main() {
        //there is no main since this isn't closed loop. hmm. save it for later.
        //it would be nice if c++ could run tests without using main().
    }

    function testRender($blocks = 128) {
        //this is like main() for tests. Returns a wave of floats that could be converted to wav.
        //note this signal should be stereo.
        $waveOut = array();
        for($i=0;$i<$blocks;$i++) {
            $wave = $this->playerEngine->renderNextBlock($i);
            //this is slow. How to make better?
            $waveOut = array_merge($waveOut, $wave);
        }
        return $waveOut;
    }
    
    function close() {}
}

