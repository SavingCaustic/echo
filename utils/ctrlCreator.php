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
    var $col0;
    var $col1;
    var $col2;
    var $font1;
    var $currAttr;
    var $debug = false;
    var $moduleXY = array(0,0);

    function __construct() {
        $this->debug = false;
        if (!function_exists('imagepng')) {
            die('you *really* need imagegd extension for this to work..');
        }
        $this->font1 = './nimbus-sans-l.bold.otf';
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
        //before trying to parse, replace any row beginning with # to <comment/>
        $rows = explode(chr(10),$s);
        for($i=0;$i<sizeof($rows);$i++) {
            if (substr(trim($rows[$i]),0,1) == '#') {
                $rows[$i] = '<comment />';
            }
        }
        $s = implode(chr(10),$rows);
        unset($rows);
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

    function tag_comment($attr,$type) {
        //nothing to do..
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
                $this->col0 = imagecolorallocate($this->bgImg, $rgb[0], $rgb[1], $rgb[2]);
                imagefill($this->bgImg, 0, 0, $this->col0);
            }
            $this->col1 = imagecolorallocate($this->bgImg,160,120,60);
            $this->col2 = imagecolorallocate($this->bgImg,200,80,30);

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
            imagerectangle($this->bgImg, $xy[0], $xy[1], $xy[0]+$wh[0], $xy[1]+$wh[1], $this->col1);
            imagerectangle($this->bgImg, $xy[0]+1, $xy[1]+1, $xy[0]+$wh[0]-1, $xy[1]+$wh[1]-1, $this->col1);
            //
            $label = strtoupper($this->getAttr('label','the label'));
            $a = imagettfbbox(20,0,$this->font1, $label);
            $w = $a[4] - $a[0];
            imagefilledrectangle($this->bgImg, $xy[0]+15, $xy[1]-5, $xy[0]+$w+25, $xy[1]+5, $this->col0);
            imagettftext($this->bgImg, 20, 0, $xy[0]+20, $xy[1]+10, $this->col1, $this->font1, $label);
            //html
        } else {
            //close
            $this->moduleXY = array(0,0);
        }
    }

    function tag_module() {
        //just an empty module, can't do much..
    }
    
    function addLabel($xy, $offset=60) {
        $label = $this->getAttr('label');
        $a = imagettfbbox(16,0,$this->font1, $label);
        $w = $a[4] - $a[0];
        imagettftext($this->bgImg, 16, 0, $xy[0] - floor($w/2), $xy[1]+$offset, $this->col1, $this->font1, $label);
    }

    function tag_optbutton($attr, $type) {
        $this->setAttr($attr);
        //xy relative to module.
        $xy = $this->getXY();
        imagerectangle($this->bgImg, $xy[0]-20, $xy[1]-20, $xy[0]+20, $xy[1]+20,$this->col1);
        $this->addLabel($xy, 60);
    }

    function tag_knob($attr, $type) {
        $this->setAttr($attr);
        //xy relative to module.
        $xy = $this->getXY();
        imagefilledarc($this->bgImg, $xy[0], $xy[1], 70, 70, 0-128-90,128-90,$this->col2,0);
        imagefilledarc($this->bgImg, $xy[0], $xy[1], 62, 62, 0-128-90,128-90,$this->col0,0);
        //
        imagefilledellipse($this->bgImg, $xy[0], $xy[1], 50, 50, $this->col1);
        $this->addLabel($xy, 60);
    }

    function tag_centerknob($attr, $type) {
        $this->setAttr($attr);
        //xy relative to module.
        $xy = $this->getXY();
        imagefilledarc($this->bgImg, $xy[0], $xy[1], 70, 70, 0-128-90,128-90,$this->col2,0);
        imagefilledarc($this->bgImg, $xy[0], $xy[1], 62, 62, 0-128-90,128-90,$this->col0,0);
        imagefilledrectangle($this->bgImg, $xy[0]-8, $xy[1] - 35, $xy[0]+8,$xy[1]-30,$this->col0);
        imagefilledellipse($this->bgImg, $xy[0], $xy[1] - 32, 6, 6, $this->col2);
        //
        imagefilledellipse($this->bgImg, $xy[0], $xy[1], 50, 50, $this->col1);
        $this->addLabel($xy, 60);
    }

    function tag_knobswitch() {}

    function tag_switch() {}

    function tag_dualknob() {}

    function tag_vslider() {}

    function tag_hslider() {}

    function tag_minibutton($attr, $type) {
        //add a mini button that can side beside of a label not being a button) 
    }

    function tag_rotaryswitch($attr, $type) {
        $this->setAttr($attr);
        //xy relative to module.
        $xy = $this->getXY();
        imagefilledellipse($this->bgImg, $xy[0], $xy[1], 50, 50, $this->col1);
        //add some dots based on count of values..
        $values = $this->getAttr('values','1,2,3');
        $valArr = explode(',',$values);
        //angle is *not* 270 like real pot, but 256 to match range of CC.
        $angle = 256 / (sizeof($valArr)-1);
        for($i=0;$i<sizeof($valArr);$i++) {
            $radAngle = round(-128+360+$angle*$i) % 360 / 180 * pi();
            $dotSin = round(cos($radAngle) * 33) * -1;
            $dotCos = round(sin($radAngle) * 33);
            //if ($i==1) die('angle: ' . $radAngle / pi() * 180 . ', dotSin: ' . $dotSin . ' , dotCos:' . $dotCos);
            //die($values);
            imagefilledellipse($this->bgImg, $xy[0] + $dotCos, $xy[1] + $dotSin, 6, 6, $this->col2);
        }

        $this->addLabel($xy, 60);
    }
}

//Dancing with myself..
$CC = new CtrlCreator();
$CC->preProcess();
$CC->process();