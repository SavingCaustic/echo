<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
/*
HOWTO: Run this from the command line with synth or effect as argument: 

> php controllerCreator.php synth/subreal

It will read the controllers.xml and generate a background image and a defaults.json
(Possibly, it could generate declarations for JUCE or any other stuff but that's for later.)
*/

class CtrlCreator {
    var $xmlFile;
    var $xml;
    var $bgImg;
    var $currAttr;
    var $debug = false;
    var $moduleXY = array(0,0);

    function __construct() {
        $this->debug = false;
        //get the argument (xml filename) and store
        if (array_key_exists('argv', $_SERVER)) {
            $argv = $_SERVER['argv'];
            if (sizeof($argv) == 1) {
                die('Enter directory for controller source, as synths/subsynth' . "\r\n");
            } else {
                $fn = $a[1];
            }
        } else {
            $fn = @$_GET['fn'];
            if (strlen($fn) == 0) {
                die('Enter directory for controller source, as fn=synths/subreal' . "\r\n");
            }
        }
        $fn = '../assets/' . $fn . '/controllers.xml';
        if (!file_exists($fn)) {
            die('No synth or effect controller file at ' . $fn . "\r\n");
        } else {
            $this->xmlFile = $fn;
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
        foreach($this->xml as $tag) {
            $tagType = ($tag['type'] == 'complete') ? 'tag_' : 'octag_';
            $method = $tagType . strtolower($tag['tag']);
            if (!method_exists($this,$method)) {
                die("Sorry, but the method $method does not exist. \n");
            }
        }
    }    

    function process() {
        //walk through the xml-file, building the background-image and defaults.json step by step.
        foreach($this->xml as $tag) {
            $tagType = ($tag['type'] == 'complete') ? 'tag_' : 'octag_';
            $method = $tagType . strtolower($tag['tag']);
            //method already verified so fire away.
            if(!array_key_exists('attributes',$tag)) $tag['attributes'] = array();
            $this->$method($tag['attributes'],$tag['type']);
        }
    }
    
    function setAttr($attr, $required = []) {
        $this->currAttr = $attr;
    }

    function getAttr($name, $default = '') {
        $name = strtoupper($name);
        if (array_key_exists($name, $this->currAttr)) {
            return $this->currAttr[$name];
        } else {
            return $default;
        }
    }

    function getXY() {
        $xy = $this->getAttr('xy','100,100');
        $a = explode(',',$xy);
        return array(floor($a[0] + $this->moduleXY[0]), floor($a[1] + $this->moduleXY[1]));
    }

    function getWH() {
        $xy = $this->getAttr('wh','100,100');
        $a = explode(',',$xy);
        return array(floor($a[0]), floor($a[1]));
    }

    function hex2rgb($hex) {
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return array($r, $g, $b);
    }

    function octag_controller($attr, $type) {
        //
    }

    function octag_panel($attr, $type) {
        //create size of dest image
        if ($type == 'open') {
            //setup bg-image and get theme
            $this->setAttr($attr);
            $size = $this->getAttr('size','640x480');
            $a = explode('x',$size);
            $this->bgImg = imagecreatetruecolor($a[0],$a[1]);
            $bgcolor = $this->getAttr('bgcolor');
            if ($bgcolor != '') {
                $rgb = $this->hex2rgb($bgcolor);
                $bgCol = imagecolorallocate($this->bgImg, $rgb[0], $rgb[1], $rgb[2]);
                imagefill($this->bgImg, 0, 0, $bgCol);
            }
        } else {
            //output image and defaults.json
            if (!$this->debug) {
                header('content-type: image/png');
                echo imagepng($this->bgImg);
                die();
            }
        }
    }

    function octag_module($attr, $type) {
        if ($type == 'open') {
            $this->setAttr($attr);
            //img
            $xy = $this->getXY();
            $this->moduleXY = $xy;
            $wh = $this->getWH();
            if ($this->debug) {
                echo serialize($xy) . ', ' . serialize($wh) . '<hr/>';
            }
            $col = imagecolorallocate($this->bgImg,255,255,255);
            imagerectangle($this->bgImg, $xy[0], $xy[1], $xy[0]+$wh[0], $xy[1]+$wh[1], $col);
            //imagerectangle($this->bgImg, $xy[0]+1, $xy[1]+1, $xy[0]+$wh[0]-1, $xy[1]+$wh[1]-1, $col);
            $label = strtoupper($this->getAttr('label','the label'));
            imagettftext($this->bgImg, 20, 0, $xy[0]+20, $xy[1]+10, $col, './nimbus-sans-l.bold.otf',$label);
            //html
        } else {
            //close
            $this->moduleXY = array(0,0);
        }
    }

    function tag_module() {
        //just an empty module, can't do much..
    }
    
    function tag_button($attr, $type) {
        $this->setAttr($attr);
        //xy relative to module.
        $xy = $this->getXY();
        $col = imagecolorallocate($this->bgImg,255,255,255);
        imagerectangle($this->bgImg, $xy[0]-20, $xy[1]-20, $xy[0]+20, $xy[1]+20,$col);
    }

    function tag_knob() {}

    function tag_centerknob() {}

    function tag_knobswitch() {}

    function tag_switch() {}

    function tag_dualknob() {}

    function tag_rotaryswitch($attr, $type) {
        $this->setAttr($attr);
        //xy relative to module.
        $xy = $this->getXY();
        $col = imagecolorallocate($this->bgImg,255,255,255);
        imagefilledellipse($this->bgImg, $xy[0], $xy[1], 50, 50, $col);
    }
}

//Dancing with myself..
$C = new CtrlCreator();
$C->preProcess();
$C->process();