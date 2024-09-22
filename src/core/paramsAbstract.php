<?php
declare(strict_types=1);

abstract class ParamsAbstract {
    abstract protected function pushNumParam($name, $val);
    abstract protected function pushStrParam($name, $val);
    //
    protected static $whoAmI = '';
    protected static $ctrlData = null;  //maybe these aren't split in num and str?
    protected static $ctrlOptions = null;
    
    //used by player, synths, effects and yada-yada.. 
    protected $numParams = array();
    protected $strParams = array();

    protected function setHowAmI($str) {
        self::$whoAmI = $str;
    }

    protected function initCtrlData($appDir) {
        if (self::$ctrlData === null) {
            $dir = $appDir . '/assets/' . self::$whoAmI . '/';
            //load json-file from assets library.
            self::$ctrlData = json_decode(file_get_contents($dir . 'ctrl_defaults.json'), true);
            self::$ctrlOptions = json_decode(file_get_contents($dir . 'ctrl_options.json'), true);
        }
    }

    public function getCtrlInfo($name, $key) {
        //key could be min, max, log, options. i dunno..
        if (self::$ctrlData === null) {
            //load the json, (must NOT be done by player)

        }
    }

    public function loadDefaultParams($path) {
        //this must not be called by player. use prepare.
        $json = file_get_contents($path);
        $array = json_decode($json,true);
        $this->numParams = $array['num'];
        $this->strParams = $array['str'];
    }

    public function setNum($name, $val) {
        if (array_key_exists($name, $this->numParams)) {
            $this->numParams[$name] = $val;
            $this->pushNumParam($name, $val);
        }
    }

    public function setStr($name, $val) {
        //only allow setting of key that already exists.
        if (array_key_exists($name, $this->strParams)) {
            $this->strParams[$name] = $val;
            $this->pushStrParam($name, $val);
        } else {
            //we can't do much because we don't have player-engine here.
        }
    }

    public function pushAllParams() {
        foreach($this->numParams as $name=>$val) {
            $this->pushNumParam($name, $val);
        }
        foreach($this->strParams as $name=>$val) {
            $this->pushStrParam($name, $val);
        }
    }

    public function getNum($name): float {
        //to support waveform etc, string it must be..
        if (array_key_exists($name, $this->numParams)) {
            $val = $this->numParams[$name];
        } else {
            $val = 0;
        }
        return $val;
    }

    public function getStr($name): string {
        //to support waveform etc, string it must be..
        if (array_key_exists($name, $this->numParams)) {
            $val = $this->strParams[$name];
        } else {
            //take a chance on me..
            $val = 'DEFAULT';
        }
        return $val;
    }

    function getAllNumParams() {
        return $this->numParams;
    }

    function getAllStrParams() {
        return $this->strParams;
    }

    function paramsToJSON() {
        $array = array(
            'num' => $this->numParams,
            'str' => $this->strParams
        );
        return json_encode($array, JSON_UNESCAPED_SLASHES);
    }

}