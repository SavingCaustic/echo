<?php

class ErrorLog {

    private int $logSize;
    private array $errorRows;
    private int $errWrIX;
    private int $errRdIX;
    
    function __construct() {
        $this->logSize = 16;
        $this->errorRows = array();
        for($i=0;$i<$this->logSize;$i++) {
            $this->errorRows[$i] = str_pad('',50);
        }
        $this->errWrIX = 0;
        $this->errRdIX = 0;
    }

    function add($msg) {
        $this->errorRows[$this->errWrIX] = $msg;
        $this->errWrIX++;
        if ($this->errWrIX > $this->logSize) $this->errWrIX = 0;
        if ($this->errWrIX == $this->errRdIX) {
            //force quit the application and output the error log to user / dev
        }
    }

    function check() {
        return ($this->errWrIX != $this->errRdIX);
    }

    function get() {
        $err = $this->errorRows[$this->errRdIX];
        $this->errRdIX++;
        if ($this->errRdIX > $this->logSize) $this->errRdIX = 0;
        return $err;
    }
}