<?php

/* struct only */
class Pattern {
    //RO
    var $data;
    var $ticksInBar;
    var $barsInPattern;
    //WR
    var $dataPtr;
    var $EOF;

    function __construct($data = array(), $ticksInBar = 768, $bars = 1) {
        $this->data = $data;
        $this->ticksInBar = $ticksInBar;
        $this->barsInPattern = $bars;
        //
        $this->dataPtr = 0;
        $this->EOF = true;
    }
}
