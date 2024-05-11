<?php
//just get the waves..
$path = '../assets/synths/waveform/background.jpg';
$t = file_exists($path);
header('Content-Type: image/jpeg');
$s = file_get_contents($path);
echo $s;