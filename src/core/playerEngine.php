<?php

declare(strict_types=1);

const TPH_SAMPLE_RATE = 48000;      //new standard in iOS and Android  
const TPH_RACK_RENDER_SIZE = 64;
const TPH_TICKS_PER_CLOCK = 8;      //PPQN = 192
const TPH_RACK_COUNT = 4;           //keep small for easy debugging..

require 'paramsAbstract.php';
require 'rotator.php';
require 'rack.php';
require 'tapeController.php';
require 'metronome.php';
require 'renderPool.php';
//require 'patternPool.php';
require 'errorLog.php';
require 'midiReciever.php';
require 'midiSender.php';

class PlayerEngine extends ParamsAbstract {
    //VERIFIED
    public $appDir;
    public $masterTune;
    public $swingCycle;
    public $swingDepth;
    public $isPlaying;                 //if patterns are running..

    protected $rackCount;                 //maximum racks
    protected $audioBufferSize;           //run-time adjustable (restart of audio device needed)

    protected $playMode;
    protected $clockReset;

    protected $playPatterns;              //playPatterns. (Timing for eventors and effects are running always.)

    protected $hErrorLog;
    protected $hMetronome;
    protected $hPatternPool;

    public $hTapeController;           //manages play, start etc.. Easy to test in php..
    protected $hRotator;                  //keeps track of ticks, swings and clicks. All that stuff..
    protected $hMidiReciever;             //
    /**
     * @var Rack[]                  //fixes syntax in VS Code
     */
    protected array $hRacks;           //array of pointers to racks

    //NOT IMPLEMENTED (YET)
    protected $hMasterMixer = null;       //mixer(?) and reverb
    protected $hSequencer = null;         //sequencer
    protected $hMidiSender;



    //VERIFIED

    function __construct() {
        require('../appdir.php');
        $this->appDir = getAppDir();
        $this->audioBufferSize = 1024;
        $this->masterTune = 440;
        $this->rackCount = TPH_RACK_COUNT;
        //global swing settings when not overridden by pattern
        $this->swingCycle = 96;
        $this->swingDepth = 0;

        $this->hErrorLog = new ErrorLog();
        $this->hRotator = new Rotator($this);
        $this->hTapeController = new TapeController($this);
        $this->hMidiReciever = new MidiReciever($this);
        $this->hMidiSender = new MidiSender($this);
        $this->hMetronome = new Metronome($this);
        //$this->hPatternPool = new PatternChainPool();

        $this->hRacks = array();
        for ($i = 0; $i < $this->rackCount; $i++) {
            $this->hRacks[$i] = null;
        }
        $this->reset();
    }

    function close() {
        //dunno. clean up everything, then quit.
        echo 'Thank you for the music.' . "\r\n";
    }

    function reset() {
        //shut down audio driver and restart it right?
        //then new value at audioBufferSize will be pushed.
        //we need device-settings in the users-directory
        $this->hRotator->reset();
        for ($i = 0; $i < $this->rackCount; $i++) {
            if (!is_null($this->hRacks[$i])) {
                $this->hRacks[$i]->reset();
            }
        }
        $this->loadDefaultParams($this->appDir . '/project_defaults.json');
        $this->clockReset();
    }

    function clockReset() {
        //executed on boot and on play.
        //Resets clock (which is constantly running)
        //problem is that interrupt may happen inside iteration, so just a flag here to set
        $this->clockReset = true;   //will be cleared on next interrupt.
    }

    function pushNumParam($key, $val) {
        switch ($key) {
            case 'bpm':
                //also get time-sign and skip separate treatment for them.
                $this->hRotator->setTempo($val);
                break;
            case 'master_tune':
                $this->masterTune = $val;
                break;
            case 'swing_cycle':
                //in clocks, so we're not dependent on PPQN
                $this->swingCycle = $val; // * TPH_TICKS_PER_CLOCK;
                break;
            case 'swing_level':
                $this->swingDepth = $val; //we don't know ticks in Pattern here.
                break;
            case 'swing_offset':
                die('to be implemented');
                break;
            default:
                die('unknown song setting: ' . $key);
                break;
        }
    }

    function pushStrParam($key, $val) {
        switch ($key) {
            case 'time_sign':
                $a = explode('/', $val);
                //verify numbers?
                //just defaults really for new patterns..
                //$this->hRotator->setTimeSign($a[0], $a[1]);
            case 'play_mode':
                //don't like to have string here. Should be enum or rename to song_play
                $this->playMode = $val;
                break;
        }
    }


    function rackSetup(int $rackIdx, string $synth) {
        //in c++, not really sure in how to allocate objects and best practice of controlling their lifetime.
        if (!is_null($this->hRacks[$rackIdx])) {
            //just drop it..
            return;
        }
        $this->hRacks[$rackIdx] = new Rack($rackIdx, $this, $this->appDir);
        $r = &$this->hRacks[$rackIdx];
        $r->loadSynth($synth);
    }

    function getRackRef(int $rackIdx): Rack {
        return $this->hRacks[$rackIdx];
    }

    function renderNextBlock($debug = 0) {
        $outerCnt = $this->audioBufferSize / TPH_RACK_RENDER_SIZE;
        $masterWave = array();
        if ($this->clockReset) {
            //ok, we need to iterate over all racks and set clock to zero.
            $this->clockReset = false;
            //not sure about this one, better if it is zero already, so tapeController waits for the next eight?
            $this->hRotator->pulse = 0;                         //possibly hRotator->reset()
            for ($i = 0; $i < $this->rackCount; $i++) {         //iterate over (t)racks. USE threads MULTI-CORE HERE
                if (!is_null($this->hRacks[$i])) {
                    $this->hRacks[$i]->clockReset();
                }
            }
        }

        for ($outer = 0; $outer < $outerCnt; $outer++) {
            $this->manageMidiInBuffer();                        //will be forwarded to resp rack
            for ($i = 0; $i < $this->rackCount; $i++) {         //iterate over (t)racks. USE threads MULTI-CORE HERE
                if (!is_null($this->hRacks[$i])) {
                    $this->hRacks[$i]->probeNewClock($this->hRotator->pulse);                    //eventors & effects..
                    if ($this->isPlaying) $this->hRacks[$i]->probeNewTick($this->hRotator->pulse);  //pattern.
                    $this->hRacks[$i]->render(1);
                }
            }
            // calculate master-wave for the rackRenderSize-block
            $wave = array_fill(0, TPH_RACK_RENDER_SIZE, 0);
            //this is more or less the mixer. SIMD would be nice here..
            for ($i = 0; $i < $this->rackCount; $i++) {
                if (!is_null($this->hRacks[$i])) {
                    for ($j = 0; $j < TPH_RACK_RENDER_SIZE; $j++) {
                        $wave[$j] += $this->hRacks[$i]->bufferOut[$j];
                    }
                }
            }
            //now output
            $targetIX = $outer * TPH_RACK_RENDER_SIZE;
            for ($i = 0; $i < TPH_RACK_RENDER_SIZE; $i++) {
                $masterWave[$targetIX + $i] = $wave[$i];
            }
            //rotate the main wheel so the rest can follow
            $newEight = $this->hRotator->frameTurn();
            if ($newEight && $this->isPlaying) {
                //we got a new eighth. Increase on all racks(?) 
                for ($i = 0; $i < $this->rackCount; $i++) {
                    if (!is_null($this->hRacks[$i])) {
                        $this->hRacks[$i]->hPatternPlayer->incEightCounter();
                    }
                }
            }
        }
        return $masterWave;
    }

    function manageMidiInBuffer() {
        //if we're recording too, we'd probably like to add them to the json-events also, right?
        //that's a job for the low-prio thread so then we push the messages to its log.
        $this->hMidiReciever->poll();
    }
}
