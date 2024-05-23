<?php
//based on the abstract class SynthController.

class SubcultController {
  var $name;
  var $type;
  var $value;
  var $options;
  var $optionIdx;

  function __construct($name, $options = null, $type = 'dial') {
    $this->name = $name;
    $this->options = $options;
    $this->type = $type;
    if($type == 'centerDial') {
      $this->value = 64;
    } else {
      $this->value = 0;
    }
  }

  function getValue() {
    return $this->value;
  }

  function setValue($value) {
    //what about options??
    $this->value = $value;
  }

  function setOption($value) {
    $test = $option / sizeof($this->options);
    $this->optionIdx = floor($val / $test);
  }
}

class lfo1 extends LFO {
  var $controllers = array();

  function declareControllers() {
    $c = &$this->controllers;
    $c[] = new Controller('rate');
    $c[] = new Controller('depth');
    $c[] = new Controller('ramp');
    $c[] = new Controller('target',array('-----','OSC1','OSC2','OSC1+2','Phase','Cutoff','Volume','Octave','Semis','Osc1 mod'));
    $c[] = new Controller('shape',array('SINUS','TRIANGLE','SAWTOOTH','SQUARE'));
  }

  public function getControllers() {
  }

  public function getController($name) {
  }

  public function setController($name,$value) {
  }
}

class lfo2 extends LFO {

  function declareControllers() {
    $c = &$this->controllers;
    $c[] = new Controller('rate');
    $c[] = new Controller('depth');
    $c[] = new Controller('target',array('-----','Osc1','Osc2','Osc1+2','Phase','Cutoff','Volume','Octave','Semis','Osc1 mod'));
  }
}

class Osc2 extends Oscillator {

  function declareControllers() {
    //den här grejen är skum. För vi vill bara ha motorn nu. UI måste ligga separat.
    $c = &$this->controllers;
    $c[] = new Controller('waveform',array('Sinus','Triangle','Sawtooth','Square','Noise'));
    $c[] = new Controller('phase',null,'centerDial');
    $c[] = new Controller('octave',array('-4','-3','-2','-1','0','1','2','3','4'),'centerDial');
    $c[] = new Controller('semis',array('-4','-3','-2','-1','0','1','2','3','4'),'centerDial');
  }
}


