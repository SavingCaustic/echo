<?php

//run all tests looking for any errors

//delete wav files. Any wav that should be kept: rename to pcm.
foreach(glob('*.wav') as $f) {
    if(strstr($f,'.wav')) {
	if($f != 'wavplayer_in.wav') unlink($f);
    }
}

//run all
foreach(glob('*.php') as $f) {
    $output = array();
    if ($f != 'all.php') {
        echo "\n Running:  $f ... \n";
        exec('php ' . $f, $output);
        echo implode("\r\n",$output);
    }
}
