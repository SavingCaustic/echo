<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('crlf', chr(13) . chr(10));
/*
HOWTO: Run this from the command line with synth or effect as argument: 

> php controllerCreator.php synth/subreal

It will read the controllers.xml and generate a background image and a defaults.json
(Possibly, it could generate declarations for JUCE or any other stuff but that's for later.)
*/

class CtrlCreator {
    var $xmlFile;
    var $xml;
    var $theme;
    var $defs;
    //refs
    var $col0;
    var $col1;
    var $col2;
    var $col3;
    //
    var $font1;
    var $font2;
    var $currAttr;
    var $debug = false;
    var $moduleXY = array(0,0);
    //output
    var $defaults;  //num
    var $strDefaults;
    var $enums;
    var $bgImg;
    var $html;
    var $imgWidths = array();
    var $rotarySteps = array();
    var $sliderLengths = array();

    function __construct() {
        $this->debug = false;
        if (!function_exists('imagepng')) {
            die('you *really* need imagegd extension for this to work..');
        }
        //get the argument (xml filename) and store
        if (array_key_exists('argv', $_SERVER)) {
            $argv = $_SERVER['argv'];
            if (sizeof($argv) == 1) {
                die('Enter directory for controller source, as synths/subsynth' . "\r\n");
            } else {
                $fn = $argv[1];
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
        //now we need to know what to output.
        //default is to output html and save image as tmp_bg.png
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
        $ok = xml_parse_into_struct($xp,$s,$vals,$indexes);
        //did we get any errors?
        if ($ok == 0) {
            $errCode =  xml_get_error_code($xp);
            $errStr =  xml_error_string($errCode);
            header('content-type: text/plain');
            echo "XML error:" . $errStr . ", line: " .  xml_get_current_line_number($xp) . "\n";
            foreach(libxml_get_errors() as $error) {
                echo "\t", $error->message;
            }
            die();
        }
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
            $name = strtoupper($name);
            $val = $this->currAttr[$name];
            if ($name == 'NAME') $val = strtoupper($val);
            return $val;
        } else {
            return $default;
        }
    }

    function addDefault($name) {
        $this->defaults[$name] = rand(0,127); //$this->getAttr('default',0);
    }

    function getXY() {
        $xy = $this->getAttr('xy','8,8');
        $a = explode(',',$xy);
        //now convert rem to px
        return array(floor($a[0]*16 + $this->moduleXY[0]), floor($a[1]*16 + $this->moduleXY[1]));
    }

    function getRelXY($size) {
        //used by html to get relative xy
        $xy = $this->getAttr('xy','8,8');
        $a = explode(',',$xy);
        return array(floor($a[0]*16 - $size), floor($a[1]*16 - $size));
    }

    function getWH() {
        $xy = $this->getAttr('wh','16,16');
        $a = explode(',',$xy);
        return array(floor($a[0]*16), floor($a[1]*16));
    }

    function hex2rgb($hex) {
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return array($r, $g, $b);
    }

    function addLabel($xy, $offset=73) {
        $label = $this->getAttr('label');
        $a = imagettfbbox(18,0,$this->font1, $label);
        $w = $a[4] - $a[0];
        imagettftext($this->bgImg, 18, 0, $xy[0] - floor($w/2), $xy[1]+$offset, $this->col0, $this->font1, $label);
    }

    function genOptImage($name, $w, $h, $labels, $font) {
        //width & height is size of box
        $padding = 3;
        $img = imagecreatetruecolor(($w+$padding*2) * sizeof($labels), $h + $padding*2);
        imagefill($img, 0,0, $this->col3);
        $i = 0;
        $x = 0;
        $fontSize = 28;
        foreach($labels as $label) {
            $x += $padding;
            imagettftext($img, $fontSize, 0, $x, floor($h / 2 + $fontSize/2), $this->col2, $font, strtoupper($label) . ' ');
            $i++;
            $x = $x + $w + $padding;
        }
        imagepng($img, 'img_' . $name . '.png');
    }
    //output now, before tags..

    function saveDefaults() {
        $ctrlPath = $this->xmlFile;
        $defaultsFile = str_replace('controllers.xml','defaults.json',$ctrlPath);
        $arr = array(
            'num' => $this->defaults,
            'str' => $this->strDefaults
        );
        file_put_contents($defaultsFile, json_encode($arr, JSON_UNESCAPED_SLASHES));
        //and enums..
        $enumFile = str_replace('controllers.xml','enums.json',$ctrlPath);
        file_put_contents($enumFile, json_encode($this->enums, JSON_UNESCAPED_SLASHES));
    }

    function tag_comment($attr,$type) {
        //nothing to do..
    }

    function tag_value($attr, $type) {
        //non visual. get the name, min, max and log.
        $this->setAttr($attr);
        $this->addDefault($this->getAttr('name'));
    }
    
    function tag_enum($attr, $type) {
        //non visual enum.
        die('to be written');
    }
    
    /* END OF BASE CLASS - BELOW SHOULD BE CUSTOMIZABLE */

    function octag_controller($attr, $type) {
        //
    }

    function octag_view($attr, $type) {
        if ($type == 'open') {
            $this->setAttr($attr);
            $this->theme = $this->getAttr('theme','bakelite');
            $this->defs = json_decode(file_get_contents('./' . $this->theme . '/defs.json'),true);
            $this->html = '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>iframed really</title>
        <link rel="stylesheet" href="' . $this->theme . '/style.css">
        <link rel="icon" href="./favicon.ico" type="image/x-icon">
        <script src="' . $this->theme . '/vue.js"></script>
    </head>
    <body>
        <div class="appContainer">
            <fdiv class="uiContainer" id="app">' . crlf;
        } else {
            $this->html .= '
            </fdiv>
            </div>
            </div>
    <script>
function updateScale() {
    const container = document.querySelector(\'.appContainer\');
    const currentWidth = window.innerWidth;
    const currentHeight = window.innerHeight;
    //the app has proportions of 2:1, right?
    let scaleFactor = currentWidth / 1920;
    if ((currentWidth / currentHeight) > 2) {
        scaleFactor = currentHeight / 960;
    }
    //scaleFactor = 0.2;
    container.style.transform = `scale(${scaleFactor})`;
}

// Initial scale update
updateScale();

// Update scale on window resize
window.addEventListener(\'resize\', updateScale);
    </script>
    </body>
</html>
';
        echo $this->html;
        }
    }

    function octag_ipanel($attr, $type) {
        //this is really stupid, for now outputs nothing.
    }

    function octag_panel($attr, $type) {
        //create size of dest image
        if ($type == 'open') {
            //setup bg-image and get theme
            $this->setAttr($attr);
            $size = $this->getAttr('size','80x40');
            $a = explode('x',$size);
            $this->bgImg = imagecreatetruecolor(round($a[0]*16),round($a[1]*16));
            $bgcolor = $this->defs['col0'];
            if ($bgcolor != '') {
                $rgb = $this->hex2rgb($bgcolor);
                $this->col0 = imagecolorallocate($this->bgImg, $rgb[0], $rgb[1], $rgb[2]);
                imagefill($this->bgImg, 0, 0, $this->col0);
            }
            $rgb = $this->hex2rgb($this->defs['col1']);
            $this->col1 = imagecolorallocate($this->bgImg,$rgb[0],$rgb[1],$rgb[2]);
            $rgb = $this->hex2rgb($this->defs['col2']);
            $this->col2 = imagecolorallocate($this->bgImg,$rgb[0],$rgb[1],$rgb[2]);
            $rgb = $this->hex2rgb($this->defs['col3']);
            $this->col3 = imagecolorallocate($this->bgImg,$rgb[0],$rgb[1],$rgb[2]);
            $this->font1 = $this->theme . '/' . $this->defs['font1'];
            $this->font2 = $this->theme . '/' . $this->defs['font2'];
            //putting this here disallows multiple panels. Not sure..
            $this->html .= '<div class="part1" id="panel" @mousedown="swypeBegin($event)" @touchstart.prevent="swypeBegin($event)" _click="clickBegin($event)"
    style="position:relative;width:' . $a[0] . 'rem;height:' . $a[1] . 'rem;background-image:url(\'tmp_bg.png\');background-size:cover;">
  ';
        } else {
            $this->saveDefaults();
            //output image and defaults.json
            imagepng($this->bgImg, 'tmp_bg.png');
            //
            /*if (!$this->debug) {
                header('content-type: image/png');
                echo imagepng($this->bgImg);
                die();
            }*/
            //the value of setting the params to their defaults is LOW.
            //values should be coming from model, *through controller*
            //$this->html .= "<textarea cols=\"160\" rows=\"4\" style=\"position:relative;top:800px;background-color:#ccc\"> {{ vueLog }} </textarea>';
            $this->html .= "</div>
<script>
preData = {  
    eventTarget: '',
    vueLog: 'testing',
    startX: 0,
    startY: 0,
    swypeAxis: '',
    rotating: false," . crlf;
foreach($this->defaults as $key=>$val) {
    $this->html .= '    cc_' . $key . ':' . $val . ',' . crlf;        
}
//add imgWidth here?
$this->html .= '    imgWidths: ' . json_encode($this->imgWidths) . ',' . crlf;
$this->html .= '    rotarySteps: ' . json_encode($this->rotarySteps) . ',' . crlf;
$this->html .= '    sliderLengths: ' . json_encode($this->sliderLengths);
$this->html .= "};
</script>
<script src=\"" . $this->theme . "/ui.js?ts=" . time() . "\"></script>
";
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
            imagefilledrectangle($this->bgImg, $xy[0], $xy[1]+$wh[1]-30, $xy[0]+$wh[0], $xy[1]+$wh[1], $this->col1);
            //
            $label = strtoupper($this->getAttr('label','the label'));
            $a = imagettfbbox(20,0,$this->font1, $label);
            $w = $a[4] - $a[0];
            imagefilledrectangle($this->bgImg, $xy[0]+15, $xy[1]-5, $xy[0]+$w+25, $xy[1]+5, $this->col0);
            imagettftext($this->bgImg, 20, 0, $xy[0]+20, $xy[1]+10, $this->col1, $this->font1, $label);
            //html
            $this->html .= '<div style="position:absolute;left:' . $xy[0] . 'px;top:' . $xy[1] . 
            'px;width:' . $wh[0] . 'px;height:' . $wh[1] . 'px;" id="module_' . $this->getAttr('name') . '" class="module">' . crlf;
        } else {
            //close
            $this->moduleXY = array(0,0);
            $this->html .= '</div>' . crlf;
        }
    }

    function tag_module() {
        //just an empty module, can't do much..
    }
    
    function tag_optbutton($attr, $type) {
        $this->setAttr($attr);
        $xy = $this->getXY();
        //xy relative to module.
        $wh = $this->getWH();
        //just a placeholder really. could be smaller
        imagerectangle($this->bgImg, $xy[0] - $wh[0]*0.5, $xy[1] - $wh[1]*0.5, $xy[0] + $wh[0]*0.5, $xy[1] + $wh[1]*0.5,$this->col1);
        $this->addLabel($xy);
        //generate image
        $values = explode(',',$this->getAttr('values','CAT,DOG'));
        if ($this->getAttr('numeric') == "yes") {
            $this->defaults[$this->getAttr('name')] = $this->getAttr('default');
        } else {
            $this->strDefaults[$this->getAttr('name')] = $this->getAttr('default');
        }
        $valCount = sizeof($values);
        $this->genOptImage($this->getAttr('name'), $wh[0], $wh[1], $values,$this->font2);
        //place it, now we need the real xy.
        $xy = explode(',',$this->currAttr['XY']);
        $xy = array($xy[0]*16, $xy[1] * 16);
        $wh = explode(',',$this->currAttr['WH']);
        $wh = array($wh[0] * 16, $wh[1]*16);
        $name = $this->getAttr('name');
        //$this->html .= '<div class="optbutton" style="position:absolute;left:0px;top:0px;">
        $this->html .= '<div class="optbuttonCont" 
        style="position:absolute;left:' . $xy[0] - $wh[0] * 0.5 . 'px;top:' . $xy[1] - $wh[1]*0.5 . 'px;">
        <div style="position:relative;overflow:hidden;width:' . $wh[0]*1 . 'px;height:' . $wh[1] . 'px;">
        <img class="optbutton" id="cc_' . $name . '" :style="calcOptButtonOffset(\'cc_' . $name . '\')"
        data-width="' . $wh[0] . '" data-type="optbutton" draggable="false" data-count="' . $valCount . '" 
        src="img_' . $this->getAttr('name') . '.png">
        </div>
        </div>' . crlf;
        $this->imgWidths['cc_' . $name] = $wh[0];
    }

    function tag_knob($attr, $type) {
        $this->setAttr($attr);
        $this->addDefault($this->getAttr('name'));
        //xy relative to module.
        $xy = $this->getXY();
        $size = strtolower($this->getAttr('size','m'));
        switch($size) {
            case 's':
                $scale = 0.8;
                $dialOffset = 25;
                break;
            case 'l':
                $scale = 1.25;
                $dialOffset = 6;
                break;
            default:
                $scale = 1;
                $dialOffset = 17;
                break;
        }
        imagefilledarc($this->bgImg, $xy[0], $xy[1], round(86 * $scale), round(86 * $scale), 0-128-90,128-90,$this->col3,0);
        imagefilledarc($this->bgImg, $xy[0], $xy[1], round(78 * $scale), round(78 * $scale), 0-128-92,128-88,$this->col0,0);
        //
        imagefilledellipse($this->bgImg, $xy[0], $xy[1], 10, 10, $this->col1);
        $this->addLabel($xy);
        //HTML - now we need xy without module offset.. and we need to re-center too..
        $xy = $this->getRelXY(60);
        $name = $this->getAttr('name');
        $this->html .= '<div class="dial" style="left:' . $xy[0]+$dialOffset . 'px;top:' . $xy[1]+$dialOffset . 'px;">
        <img class="knob" id="cc_' . $name . '" data-type="knob" width="' . round(80 * $scale) . '" draggable="false" 
        src="' . $this->theme . '/cap.png" :style="calcKnobRotation(\'cc_' . $name . '\')"/>
        </div>' . crlf;
        //$this->html .= '<input class="big" style="width:40px;" v-model="cc_' . $name . '" />' . crlf;
    }

    function tag_centerknob($attr, $type) {
        $this->setAttr($attr);
        $this->addDefault($this->getAttr('name'));
        //xy relative to module.
        $xy = $this->getXY();
        $size = strtolower($this->getAttr('size','m'));
        switch($size) {
            case 's':
                $scale = 0.8;
                $dialOffset = 25;
                break;
            case 'l':
                $scale = 1.25;
                $dialOffset = 6;
                break;
            default:
                $scale = 1;
                $dialOffset = 17;
                break;
        }
        imagefilledarc($this->bgImg, $xy[0], $xy[1], round(86 * $scale), round(86 * $scale), 0-128-90,128-90,$this->col3,0);
        imagefilledarc($this->bgImg, $xy[0], $xy[1], round(78 * $scale), round(78 * $scale), 0-128-92,128-88,$this->col0,0);
        imagefilledrectangle($this->bgImg, $xy[0]-round(9 * $scale), $xy[1] - round(42 * $scale), $xy[0]+round(9 * $scale),$xy[1]-round(25 * $scale),$this->col0);
        imagefilledellipse($this->bgImg, $xy[0], $xy[1] - round(41 * $scale), round(8 * $scale), round(8 * $scale), $this->col3);
        //
        imagefilledellipse($this->bgImg, $xy[0], $xy[1], 10, 10, $this->col1);
        $this->addLabel($xy);
        //HTML - now we need xy without module offset.. and we need to re-center too..
        $xy = $this->getRelXY(60);
        $name = $this->getAttr('name');
        //for now, I can't see in what way this would be different in BE-communication than
        //an ordinary knob, so removing centerknob now from class and data-type
        $this->html .= '<div class="dial" style="left:' . $xy[0]+$dialOffset . 'px;top:' . $xy[1]+$dialOffset . 'px;">
        <img class="knob" id="cc_' . $name . '" data-type="knob"  width="' . round(80 * $scale) . '" draggable="false"
        src="' . $this->theme . '/cap.png"  :style="calcKnobRotation(\'cc_' . $name . '\')" >
        </div>' . crlf;
    }

    function tag_knobswitch() {}

    function tag_switch() {}

    function tag_dualknob() {}

    function tag_vslider($attr, $type) {
        $this->setAttr($attr);
        $this->addDefault($this->getAttr('name'));
        //xy relative to module.
        $xy = $this->getXY();
        $size = $this->getAttr('size','m');
        $wh = array(3*16,12*16); //h is stroke. Line needs to be a bit longer.
        $width = $wh[0] / 20;
        imagefilledrectangle($this->bgImg, 
        round($xy[0]-$width),round($xy[1]-$wh[1]/1.8),
        round($xy[0]+$width),round($xy[1]+$wh[1]/1.8),$this->col1);
        //hardcoded again, improve..
        $this->addLabel($xy, $wh[1]/2+66);
        //html
        $name = $this->getAttr('name');
        //height of image has to be calculated for the height of the containing div
        //background-color:rgba(200,50,50,0.6);
        $cHeight = $wh[1] + 80 - 30; //this calculation needs to take into account that slider is drawn from center
        $this->html .= '<div class="slider" style="left:' . 13 . 'px;top:' . '20' . 'px;height:' . $cHeight . 'px;">
        <img class="vslider" id="cc_' . $name . '" data-type="vslider" data-length="' . $wh[1] . '" width="80" draggable="false"
        src="' . $this->theme . '/vslider.png" :style="calcVsliderPosition(\'cc_' . $name . '\')" >
        </div>' . crlf;
        $this->sliderLengths['cc_' . $name] = $wh[1];
    }

    function tag_hslider() {}

    function tag_minibutton($attr, $type) {
        //add a mini button that can side beside of a label not being a button) 
    }

    function tag_microbutton($attr, $type) {
        //add an even smaller, like silly small button to sit somewhere.
    }

    function tag_rotaryswitch($attr, $type) {
        $this->setAttr($attr);
        $this->addDefault($this->getAttr('name'));
        //xy relative to module.
        $xy = $this->getXY();
        $potSize = 10;
        imagefilledellipse($this->bgImg, $xy[0], $xy[1], $potSize, $potSize, $this->col1);
        //add some dots based on count of values..
        $values = $this->getAttr('values','1,2,3');
        $valArr = explode(',',$values);
        //angle is *not* 270 like real pot, but 256 to match range of CC.
        $angle = 256 / (sizeof($valArr)-1);
        $dotPotSize = 41;
        $enums = [];
        for($i=0;$i<sizeof($valArr);$i++) {
            $enums[] = $valArr[$i];
            $radAngle = round(-128+360+$angle*$i) % 360 / 180 * pi();
            $dotSin = round(cos($radAngle) * $dotPotSize) * -1;
            $dotCos = round(sin($radAngle) * $dotPotSize);
            //if ($i==1) die('angle: ' . $radAngle / pi() * 180 . ', dotSin: ' . $dotSin . ' , dotCos:' . $dotCos);
            //die($values);
            imagefilledellipse($this->bgImg, $xy[0] + $dotCos, $xy[1] + $dotSin, 6, 6, $this->col3);
        }
        $this->enums[$this->getAttr('name')] = $enums;
        $this->addLabel($xy);
        //HTML
        $xy = $this->getRelXY(60);
        $name = $this->getAttr('name');
        $this->html .= '<div class="dial" style="left:' . $xy[0]+17 . 'px;top:' . $xy[1]+17 . 'px;">
        <img class="rotaryswitch" id="cc_' . $name . '" data-type="rotaryswitch" width="80" draggable="false"
        src="' . $this->theme . '/cap.png" :style="calcRotarySwitchRotation(\'cc_' . $name . '\')">
        </div>' . crlf;
        $this->rotarySteps['cc_' . $name] = sizeof($valArr);
        //$this->html .= '<input class="big" style="width:40px;" v-model="cc_' . $name . '" />' . crlf;
    }

    function tag_keyboard($attr,$type) {
        //just for show now..
        $this->setAttr($attr);
        $keys = $this->getAttr('keys',24); //on a 12-tone scale
        $width = 1900;
        $kwWhite = $width / $keys * 12 / 7;
        $s = '<div style="position:relative;top:700px;left:0px;">asdf' . crlf;
        for($i=0;$i<$keys;$i++) {
            $s .= '<div class="whiteKey"></div>' . crlf;
        }
        //start at either C or F. Enough.
        $s .= '</div>' . crlf;
        return $s;
    }
}

//Dancing with myself..
$CC = new CtrlCreator();
$CC->preProcess();
$CC->process();