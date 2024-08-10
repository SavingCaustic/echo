<?php

declare(strict_types=1);

class Rotator {
    //protected $playerEngine;
    public    $pulse;                   //Float 0 - 96 (192PPQN)
    private   $pulsesPerFrame;          //float

    function __construct($playerEngine) {
        //also racks need to setup reference to playerEngine since tempo is there.
        //$this->playerEngine = &$playerEngine;
    }

    public function reset(): void {
        $this->pulse = 0;
    }

    public function setTempo($bpm): void {
        $eightsPerSec = $bpm / 60 * 2;
        $this->setPulsesPerFrame($eightsPerSec);
    }

    private function setPulsesPerFrame($eps): void {
        //4 eps (120pbm) = 
        $this->pulsesPerFrame = $eps * TPH_RACK_RENDER_SIZE / TPH_SAMPLE_RATE * TPH_TICKS_PER_CLOCK * 12; //12 not 24 since /8
    }

    public function frameTurn(): bool {
        //called *after* render actions in playerEngine.
        //returns true if we're entering a new eight.
        $this->pulse = round($this->pulse + $this->pulsesPerFrame, 3);
        if ($this->pulse >= 12 * TPH_TICKS_PER_CLOCK) {
            $this->pulse -= 12 * TPH_TICKS_PER_CLOCK;
            return true;
        } else {
            return false;
        }
    }
}
