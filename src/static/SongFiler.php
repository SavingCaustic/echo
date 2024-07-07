<?php
require_once '../appdir.php';
class SongFiler {

    public static function list($byDate = false) {
        //return array of song names, in alphabetical order, or by date?
        $path = getUserDir();
        $songs = array();
        foreach (glob($path . '/songs/*', GLOB_ONLYDIR) as $filename) {
            $mtime = filemtime($filename);
            $songs[] = array(basename($filename),$mtime);
        }
        if ($byDate) {
            //newest first
            $sortCol = array_column($songs,1);
            array_multisort($sortCol, SORT_DESC, $songs);
        } else {
            //sort uppercase
            $sortCol = array();
            foreach($songs as $song) {
                $sortCol[] = strtoupper($song[0]);
            }
            array_multisort($sortCol, SORT_ASC, $songs);
        }
        //for now, just return a one-dim array.
        $list = array();
        foreach($songs as $song) {
            $list[] = $song[0];
        }
        return $list;
    }

    public static function load($name, $ptrPlayer) {
        //loads a song into the racks. this is big..
        self::loadSongSettings($name, $ptrPlayer);
        self::loadSongRacks($name, $ptrPlayer);
        self::loadSongPatterns($name, $ptrPlayer);
        //self::loadSongSequence($name, $ptrPlayer);
    }

    public static function save($name) {
        //save all the racks, patterns, patches etc into files or sqlite.
    }

    public static function delete($name) {
        //finally a delete method.. :)
    }

    private static function getSongDir($name) {
        $path = getUserDir() . '/songs/' . $name;
        return $path;
    }

    private static function loadSongSettings($name, $ptrPlayer) {
        //settings such as tempo, master-tune etc.
        $path = self::getSongDir($name);
        //project?? Really..
        $json = file_get_contents($path . '/project.json');
        $data = json_decode($json, true);
        $ptrPlayer->reset();
        $ptrPlayer->settings = $data;
        $ptrPlayer->pushAllParams();
    }

    private static function loadSongRacks($name, $PE) {
        //load the json-file, iterate over it and load all the racks with settings
        //for respective eventor, synth and effects.
        $path = self::getSongDir($name);
        $json = file_get_contents($path . '/racks.json');
        $data = json_decode($json, true);
        foreach($data['racks'] as $rackData) {
            $PE->rackSetup($rackData['slot'], $rackData['synth']['type']);
            $myRack = $PE->rackRefs[$rackData['slot']];
            //load cached patch data into synth.
            $mySynth = $myRack->getSynthRef();
            $params = $rackData['synth']['params'];
            foreach($params as $key=>$val) {
                echo 'setting ' . $key . ' to ' . $val . "\n";
                $mySynth->setParam($key,$val);
            }
            //skip eventors, delays and stuff for now..
            //just load current pattern.
            $json = file_get_contents($path . '/pattern_R' . substr('0' . $rackData['slot'],-2,2) . '_' . substr('0' . $rackData['curr_pattern'],-2,2) . '.json');
            $patternData = json_decode($json, true);
            $signArr = explode('/', $patternData['time_sign']);            
            $myRack->loadPattern($patternData['events'],$patternData['length'], $signArr[0],$signArr[1]);
        }
    }

    private static function loadSongPatterns($name, $PE) {
        //dunno really what to do here. We've loaded curr_pattern, enough?
    }

    private static function loadSongSequence($song) {
        //maybe not needed in playerEngine. Load as we go along..
    }
}
