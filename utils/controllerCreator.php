<?php
/*
HOWTO: Run this from the command line with synth or effect as argument: 

> php controllerCreator.php synth/subsynth

It will read the controllers.xml and generate a background image and a defaults.json
(Possibly, it could generate declarations for JUCE or any other stuff but that's for later.)
*/

class ControllerCreator {
    var $xmlFile;

    function __construct() {
        //get the argument (xml filename) and store
        $a = $_SERVER['argv'];
        if (sizeof($a) == 1) {
            die('Enter directory for controller source, as synths/subsynth' . "\r\n");
        } else {
            $fn = '../' . $a[1] . '/controllers.xml';
            if (!file_exists($fn)) {
                die('No synth or effect controller file at ' . $fn . "\r\n");
            } else {
                $this->xmlFile = $fn;
            }
        }
    }

    function parseXml() {
        $s = file_get_contents($this->xmlFile);
        $xp = xml_parser_create('UTF-8');
        xml_parse_into_struct($xp,$s,$vals,$indexes);
        //Remove the cdata rows.
        $a = array();
        foreach($vals as $row) {
            if ($row['type'] != 'cdata') $a[] = $row;
        }
        $this->xml = $a;
    }

    function preProcess() {
        $this->parseXml();
        //we should probably look into what type of xml it is and load a tag-parser
        //for now, it's a one filer, validate all methods exists.
        echo "Preparsing..\n";
        foreach($this->xml as $tag) {
            //ok, php rocks right..
            $tagType = ($tag['type'] == 'complete') ? 'tag_' : 'octag_';
            $method = $tagType . strtolower($tag['tag']);
            if (!method_exists($this,$method)) {
                die("Sorry, but the method $method is a star yet not discovered. \n");
            }
        }
    }    

    function process() {
        //walk through the xml-file, building the background-image and defaults.json step by step.
        foreach($this->xml as $tag) {
            //ok, php rocks right..
            $tagType = ($tag['type'] == 'complete') ? 'tag_' : 'octag_';
            $method = $tagType . strtolower($tag['tag']);
            //method already verified so fire away.
            if(!array_key_exists('attributes',$tag)) $tag['attributes'] = array();
            $this->$method($tag['attributes'],$tag['type']);
        }
    }
    
    function octag_panel($attributes, $type) {
        //create size of dest image
        if ($type == 'open') {
            //setup bg-image and get theme
        } else {
            //output image and defaults.json
        }
    }

    function octag_module() {
        //request from theme to create module background, text, yada yada..
    }

    function tag_module() {
        //just an empty module, can't do much..
    }
    
    function tag_knob() {}

    function tag_centerknob() {}

    function tag_knobswitch() {}

    function tag_switch() {}

    function tag_dualknob() {}

    function tag_rotaryswitch() {}
}

//Dancing with myself..
$C = new ControllerCreator();
$C->preProcess();
$C->process();