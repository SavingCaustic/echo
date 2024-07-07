<?php

//generate waveforms. somewhere state degrees of waveform?

class LotGenerator {

    public static function genFromHarmonics($size, $deg, $harmonics = array(0,1), $pTarget) {
        //if harmonics is just array, easier to avoid decimal problems. First elm is 0.5f
        //generate a lookup-table of harmonics provided
        //we've recieved a pointer right? But that's a bit unsecure really..
        //i'd prefer 720deg really. Can we do it?
        $deg = 720;
        for($i=0;$i<$size;$i++) {
            $sample = 0;
            for($j=0;$j<sizeof($harmonics);$j++) {
                $sample += sin($i * $j/2) / $deg * pi();
            }
            $pTarget[$i] = $sample;
        }
    }

    public static function genSine($size, $deg, $pTarget) {
        self::genFromHarmonics($size, $deg, array(0,1), $pTarget);
    }

    public static function genSquare($size, $deg, $pTarget) {
        $harmonics = array();
        for($i=1;$i<11;$i=$i+2) {
            $harmonics[] = 0; //odd
            $harmonics[] = array($i, $i / ($i*$i));
        }
        self::genFromHarmonics($size, $deg, $harmonics, $pTarget);
    }

    public static function genSawtooth($size, $deg, $pTarget) {
        $harmonics = array();
        for($i=1;$i<11;$i++) {
            $harmonics[] = 0; //odd
            $harmonics[] = array($i, $i / ($i));
        }
        self::genFromHarmonics($size, $deg, $harmonics, $pTarget);
    }

    public static function genTriangle($size, $deg, $pTarget) {
        $harmonics = array();
        for($i=1;$i<11;$i++) {
            $harmonics[] = 0; //odd
            $harmonics[] = array($i, $i / ($i*$i));
        }
        self::genFromHarmonics($size, $deg, $harmonics, $pTarget);
    }

    public static function genNoise($size, $deg, $pTarget) {
        //works, if size if bigger than SR/20Hz => 44100/20 => 2048 (21 Hz)
        //or does it?
        $seed = 235325325;
        $a = 1664525;
        $b = 1013904223;
        $c = pow(2, 24);
        for($i=0;$i<$size;$i++) {
            $seed = ($a * $seed + $b) % $c;
            $pTarget[$i] = ($seed / $c * 2 - 1); 
        }
    }
    
}