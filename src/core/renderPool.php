<?php
//when are we starting up this?
//and when to close down?
//is there any time of the day this shouldn't be running?
//could be.. When we need 

class RenderWorker {
    //Tell me what rack to process and I'll do it.
    //But we need a way to signal back to the pool that we're done.
    var $playerEngine;
    var $working;

    function __construct($playerEngine) {
        //i dunno...
        $this->playerEngine = &$playerEngine;
        $this->working = false;
    }

    function render($rackID) {
        //dunno if we shold pass a pointer directly to the rack
        $this->working = true;
        $this->$rackID = $rackID;
        $this->playerEngine->racks[$rackID]->render();
        $this->working = false;
    }

    function __destruct() {
        //duh..
    }
}

class RenderPool {
    var $playerEngine;
    var $workerCount;
    var $renderWorkers;

    function __construct($playerEngine) {
        $this->playerEngine = $playerEngine;
    }

    function __destruct() {
        $this->closedown();
    }

    function startup($count) {
        if ($count > 4) $count = 4;
        for ($i = 0; $i < $count; $i++) {
            $this->renderWorkers[$i] = new RenderWorker($this->playerEngine);
        }
        $this->workerCount = $count;
    }

    function closedown() {
        //this could take while..
        for ($i = 0; $i < $this->workerCount; $i++) {
            $fc = 0;
            while ($this->renderWorkers[$i]->working && $fc < 100) {
                usleep(1000);
                $fc++;
                //try again..
            }
            //calling the destructor
            unset($this->renderWorkers[$i]);
        }
    }
}
