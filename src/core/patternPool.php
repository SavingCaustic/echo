<?php
//DEPRECATED!
//(Vector created in low-priority process and pointer passed to patternPlayer)

declare(strict_types=1);

//the pattern-pool is a pool of fixed size arrays/vectors that are used on pattern playback.
//they are initalized on startup and *no heap operations* are needed at runtime.

define("TPH_PATTERN_POOL_SIZE", 8);     //small now so we can view it in debug..
define("TPH_PATTERN_CHAIN_UNIT_SIZE", 64);

//aka Struct
class Event {
    public int $pulse;
    public int $midiCmd;
    public int $midiParam1;
    public int $midiParam2;

    function __construct($pulse = 0, $midiCmd = 0, $midiParam1 = 0, $midiParam2 = 0) {
        $this->pulse = $pulse;
        $this->midiCmd = $midiCmd;
        $this->midiParam1 = $midiParam1;
        $this->midiParam2 = $midiParam2;
    }
}

class ChainUnit {
    /** 
    * @var Event[]
    */
    public array $events;
    public int $usedByChainIX;          //-1 if available
    public int $nextChainUnitID;       //should have no special meaning

    function __construct() {
        $this->usedByChainIX = -1;
        for ($i = 0; $i < TPH_PATTERN_CHAIN_UNIT_SIZE; $i++) {
            $this->events[$i] = new Event();
        }
    }
}

class PatternChain {
    public int $firstChainUnitID;
    public int $lastChainUnitID;
    public int $eventCount;
    //rack??

    function __construct() {
        $this->firstChainUnitID = -1;
        $this->lastChainUnitID = -1;        //no special meaning?
        $this->eventCount = 0;
    }
}

class PatternChainPool {
    /**
     * @var ChainUnit[]             
     */
    public array $ecPool; //name??
    /**
     * @var PatternChain[]             
     */
    public array $patternChains;
    public int $bookerCounter;

    function __construct() {
        for ($i = 0; $i < TPH_PATTERN_POOL_SIZE; $i++) {
            $this->ecPool[$i] = new ChainUnit();
        }
        for ($i = 0; $i < TPH_RACK_COUNT * 2; $i++) {
            //double to support the use of next pattern in queue.
            $this->patternChains[$i] = new PatternChain();
        }
        $this->bookerCounter = 0;
    }

    function calcChainIX($rackID, $next = false) {
        $ix = $rackID * 2;
        if ($next) $ix++;
        return $ix;
    }

    function bookChainUnit($rackID, $next = false) {
        $chainIX = $this->calcChainIX($rackID, $next);
        //well we have two bookers per rack. The current playing and the next, right?
        //only make 16 tries.
        $foundFreeChain = false;
        $i = 0;
        while (!$foundFreeChain && $i < 16) {
            if ($this->ecPool[$this->bookerCounter]->usedByChainIX == -1) {
                //found!
                $foundFreeChain = true;
            } else {
                //no luck, try next..
                $i++;
                $this->bookerCounter = ($this->bookerCounter + 1) % TPH_PATTERN_POOL_SIZE;
            }
        }
        if ($foundFreeChain) {
            //book a new vector to the rack
            $this->ecPool[$this->bookerCounter]->usedByChainIX = $chainIX;
            if ($this->patternChains[$chainIX]->firstChainUnitID == -1) {
                //start a new chain
                $this->patternChains[$chainIX]->firstChainUnitID = $this->bookerCounter;
                $this->patternChains[$chainIX]->lastChainUnitID = $this->bookerCounter;
                $this->patternChains[$chainIX]->eventCount = 0;
            } else {
                //refer this new to the next-value of the old lastVectorID 
                $this->ecPool[$this->patternChains[$chainIX]->lastChainUnitID]->nextChainUnitID = $this->bookerCounter;
                //now add it,
                $this->ecPool[$this->bookerCounter]->usedByChainIX = $chainIX;
                $this->ecPool[$this->bookerCounter]->nextChainUnitID = $this->bookerCounter;
                // update chain header.
                $this->patternChains[$chainIX]->lastChainUnitID = $this->bookerCounter;
            }
        } else {
            //no real idea of what to do if vector can't be allocated..
            //maybe output to error log so we should have a connection to playerEngine after all..            
        }
        //if ($this->patternChains[$rackID]->firstChainUnitID == -1) {
        //no chain so WTF? 
        //}
        $this->bookerCounter = ($this->bookerCounter + 1) % TPH_PATTERN_POOL_SIZE;
    }

    function releaseChainForRack($rackID, $next = false) {
        $chainIX = $this->calcChainIX($rackID, $next);
        //only all chain-units can be deleted.
        $poolIX = $this->patternChains[$chainIX]->firstChainUnitID;
        //if no chain on rack, return
        if ($poolIX == -1) return;
        //repeat steps until poolIX == header lastChainUnitID
        $running = true;
        while ($running) {
            $nextPoolIX = $this->ecPool[$poolIX]->nextChainUnitID;
            //release this one..
            $this->ecPool[$poolIX]->usedByChainIX = -1;
            $this->ecPool[$poolIX]->nextChainUnitID = -1;
            //maybe also should clean up event data?
            if ($poolIX == $this->patternChains[$chainIX]->lastChainUnitID) {
                //we're done, clean up header
                $this->patternChains[$chainIX]->firstChainUnitID = -1;
                $this->patternChains[$chainIX]->lastChainUnitID = -1;
                $this->patternChains[$chainIX]->eventCount = 0;
                $running = false;
            } else {
                //loop more
                $poolIX = $nextPoolIX;
            }
        }
    }

    function walkTheChain($rackID, $next = false) {
        $chainIX = $this->calcChainIX($rackID, $next);
        $id = $this->patternChains[$chainIX]->firstChainUnitID;
        $goal = $this->patternChains[$chainIX]->lastChainUnitID;
        echo 'starting with ' . $id;
        $running = true;
        while ($running) {
            $id = $this->ecPool[$id]->nextChainUnitID;
            echo ', leading to ' . $id;
            if ($id == $goal) $running = false;
        }
        echo ' and landning on ' . $id . "\r\n";
    }
}
/*
$PP = new PatternChainPool();
$PP->bookChainUnit(5);
$PP->bookChainUnit(5);
$PP->bookChainUnit(3);
$PP->bookChainUnit(3);
$PP->bookChainUnit(5);
$PP->bookChainUnit(3);
$PP->bookChainUnit(3);
$PP->bookChainUnit(5);
$PP->walkTheChain(5);
$PP->walkTheChain(3);

$PP->releaseChainForRack(5);
//we should somehow get info on when booking is needd.. TBA...
die(print_r($PP->ecPool));
*/