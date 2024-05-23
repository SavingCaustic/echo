<?php
declare(strict_types=1);

require('dspParts/oscillator.php');
require('dspParts/noise.php');
require('dspParts/ar.php');
require('dspParts/adshr.php');
require('dspParts/adshr2.php');
require('dspParts/lfo.php');
require('dspParts/butterLpf.php');
require(__DIR__ . '/../synths/synthInterface.php');
require(__DIR__ . '/../effects/effectInterface.php');

//not sure what should be included here really. Acts as a foundation for any synth or effect,
//to be investigated..

class DspCore {
  var $sampleRate;
  var $masterTune;
  var $rackRenderSize;
  var $masterRenderSize;
  var $e12;
  var $ln2;
  var $waveTables = array();

  function __construct($sampleRate, $masterTune, $rackRenderSize, $masterRenderSize) {
    $this->sampleRate = $sampleRate;
    $this->masterTune = $masterTune;
    $this->rackRenderSize = $rackRenderSize;
    $this->masterRenderSize = $masterRenderSize;
    $this->ln2 = log(2);
    $this->setupWavetables();
  }

  function setupWavetables() {
    //wavetables of sin, tri, square, saw and noise.
    $a = array();
    $sineSize = 1024;
    for($i=0;$i<$sineSize;$i++)  $a[$i] = sin($i * pi()*2 / $sineSize);
    $this->waveTables['sine'] = $a;

    //alternate tri-wave, softened to avoid digital artifacts on pitch LFO
    for($i=0;$i<1024;$i++)  $a[$i] = 
      sin($i * pi()*2 / 1024)*0.7 + 
      sin($i * pi()*2*3 / 1024)/90 + 
      sin($i * pi()*2*5 / 1024)/25 + 
      sin($i * pi()*2*7  / 1024)/49 + 
      sin($i * pi()*2*9  / 1024)/81;
    $this->waveTables['triangle'] = $a;

    //standard tri 
    for($i=0;$i<1024;$i++) {
      $val = ($i % 256) / 255;
      if ($i % 512 > 255) $val = 1 - $val; 
      if ($i > 511) $val = $val * -1; 
      $a[$i] = $val;
    }
    $this->waveTables['triangle'] = $a;

    //alternate square-wave, softened to avoid digital artifacts on pitch LFO
    for($i=0;$i<1024;$i++)  $a[$i] = 0.9 * ( 
      sin($i * pi()*2 / 1024) + 
      sin($i * pi()*2*3 / 1024)/3 + 
      sin($i * pi()*2*5 / 1024)/5 + 
      sin($i * pi()*2*7  / 1024)/7 +
      sin($i * pi()*2*9  / 1024)/9 
    );
    $this->waveTables['square'] = $a;
    
    $a = array();   //clear
    for($i=0;$i<256;$i++) {
      if ($i<128) {
        $a[$i] = $i / 128;
      } else {
        $a[$i] = -1 + ($i % 128) / 128;  //0 - 
      }
    }
    $this->waveTables['saw'] = $a;

    $a = array();
    for($i=0;$i<4096;$i++) {
      $a[$i] = rand(-100000,100000)/100000;
    }
    $this->waveTables['noise'] = $a;

  }

  function noteToHz($note, $cent = 0) {
    //note = float!
    //return $this->masterTune * pow($this->e12,$note - 69 + $cent / 100);
    //improved:
    $oct = ($note - 69 + $cent / 100)/12;
    //return $this->masterTune * pow(2,$oct);
    return $this->masterTune * exp($this->ln2 * $oct);
  }

}
