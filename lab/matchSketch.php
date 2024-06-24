<?php
//play with CC => val => CC

$min = 1;
$max = 1000;
$kLog = 1;
$ccVal = 126;

//what about center?
if ($kLog == 0) {
    //linear
    $val = ($max-$min) * ($ccVal / 127) + $min;
} else {
    $calc = log(($max + $kLog - $min)/$kLog,2)/127;
    $val = pow(2,$calc * $ccVal) * $kLog - $kLog + $min;
}

echo 'the val:' . $val . "\r\n";

//now bring it back..
if ($kLog == 0) {
    $cc = round(($val - $min) / ($max - $min) * 127);
    if ($cc > 127) $cc = 127;
} else {
    $cc = log(($val+$kLog-$min) / $kLog,2) / $calc;;
}

echo 'the CC:' . $cc;

echo "\r\n";
