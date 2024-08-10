<?php
declare(strict_types=1);
//can't be used by the player engine itself.

abstract class ParamsAbstract {
    //used by player, synths, effects and yada-yada.. 
    protected $params = array();

    public function loadDefaultParams($path) {
        //this must not be called by player. use prepare.
        $json = file_get_contents($path);
        $this->params = json_decode($json,true);
    }


    public function setParam($name, $val) {
        //only allow setting of key that already exists.
        if (array_key_exists($name, $this->params)) {
            $this->params[$name] = $val;
            $this->pushParam($name);
        } else {
            //we can't do much because we don't have player-engine here.
        }
    }

    protected function pushParam($name) {
        //must be overridden by implementation
        die('this method must be overridden');
    }

    function pushAllParams() {
        foreach($this->params as $name) {
            $this->pushParam($name);
        }
    }

    function getParam($name): string {
        //to support waveform etc, string it must be..
        if (array_key_exists($name, $this->params)) {
            $val = (string) $this->params[$name];
        } else {
            $val = '';
        }
        return $val;
    }

    function getAllParams() {
        //we can't do the the actual save right.. Different destinations..
        //we could just return a pointer to the settings but really not cleaver right?
        //JSON?
        $output = $this->params;
        return $output;
    }

}