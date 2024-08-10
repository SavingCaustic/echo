<?php

class FastMath {
    
    static function sin($x) {
        //no use in php
        return sin($x);
    }

    static function swingCalc($tick, $swingPeriod, $swingDepth) {
        //look in advance.
        $angle = $tick % $swingPeriod;
        $lag = (1 - cos($angle/$swingPeriod * pi())) * $swingDepth;
        return $lag;
    }

    static function noteToHz($masterTune, $note, $cent = 0) {
        //note = float!
        return $masterTune * exp(M_LN2 * ($note - 69 + $cent / 100)/12);
    }

    static function dec2db($dec) {
        //log 20*$dec;
    }
}