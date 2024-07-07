<?php

class ProjectManager {
    var $settings;
    var $playMode;
    var $tempo;
    var $masterTune;
    var $timeSignNumer;
    var $timeSignDenom;
    var $racks;
    //are these reloaded to racksPlayer on play?!
    //storage of playMode, tempo(?) etc..
    //how to setup the racks based on the settings.

    function __construct() {

    }

    function reset() {
        $this->playMode = 'pattern';
    }

    function loadProject($name) {
        //what needs to be loaded and what can be left offline?
        $this->settings = json_decode(file_get_contents('projects/' . $name . 'project.json'),true);
    }

    function setOpt($name, $val) {
        $this->settings[$name] = $val;
        $this->pushSetting($name);
    }

    function pushSetting($name) {
        switch($name) {
            case 'playMode':
                $this->playMode = $this->settings[$name];
                break;
            case 'tempo':
                $this->tempo = $this->settings[$name];
                break;
            case 'master_tune':
                $this->masterTune = $this->settings[$name];
                break;
            case 'time_sign':
                $timeSign = $this->settings[$name];
                $a = explode('/', $timeSign);
                $this->timeSignNumer = $a[0];
                $this->timeSignDenom = $a[1];
                break;
            case 'racks':
                //this is a whole racks array...
                $this->racks = $this->settings[$name];
                $this->setupRacks();
        }
    }

    function setupRacks() {
        foreach($this->racks as $rack) {
            //alright, based on info, we should setup rackup X,
            //with components Y & Z.
            //we should know last played pattern and that should load too.
            $this->playerEngine->setupRack('');
            //this the way to do it??

        }
    }

    /*
    which way really??
    setup() {
      //playerEngine required
      //projectManager. Stupid to have as object. Or?
      //so
      masterPlayer
      + projectManager
      + patchManager
    }

    *WHY* should projectManager be a part of racksPlayer. I don't buy it.
    it's a part of main. But what about tempo etc? Maybe used by player? Trick.

    And what about the WS-server? Incoming request.
    project->setVal('master_tune',432);
    //go into project, use push etc..
    project->racks->8->setEventor1('trigger');
    //dunno..

    main() {
      bufferReads();
      bufferWrites();
      wsServerDo();
    }

    */


    function saveProject($name) {
        //what has already been saved? Samples? Patterns? Patches?
    }

    function pushSettings() {
        //duh..
    }


}