<?php

class RetriggerModel {
    //simple delay acting more or less as an interface for writing effects
    var $lfp;       //object for a filter - tape effect.. can we really motivate that?
    var $time;
    var $feedback;
    var $mix;
    var $fifo;
    var $fifoSize;    //sampleFreq * time
    var $fifoIdx;     //wr and rd same for now..
    var $fifoMax;

    function __construct($dspCore) {
        $this->dspCore = &$dspCore;
        //$this->lpf = new ResonantLowPassFilter(44100,100,2);
        //$this->lpf = new ButterLPFopt(44100,1000);
        $this->initSettings(); //dunno..
        $this->pushSettings();
    }

    function initSettings() {
        //TIMING IN 24 PPQN. 
        $this->settings = array(
            'NOTE_LENGTH' => 3,
            'FREQ' => 6,
        );
        //save these default settings to be picked up by www-player
        file_put_contents(__DIR__ . '/defaults.json',json_encode($this->settings));
    }

    function pushSettings() {
        //experimental function that pushes settings to non-readable, optimized registers.
        $se = $this->settings;
        $this->note_length = $se['NOTE_LENGTH'];
        $this->freq = $se['FREQ'];
    }

    function process() {
        //requested on any clock.

        //any eventor *breaks* the path between the event-input (midi) and synth.
        //this process-method takes over feeding event signals to the synth.
        //this needs to be some kind of state-machine keeping tracks pressed notes.
        //it must also respond to allNotesOff.         
    }

    function play() {}

    function stop() {}

    function panic() {
        //the panic signal is sent to the rack and re-distributed to the eventor, synth, and effects
        //just close any open states. Leave it to the synth to any running voices.
    }
}

