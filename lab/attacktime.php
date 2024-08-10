<?php

$t = 0.1;
$sr = 48000;
$bs = 64;
$cv = 0;
$tv = 0.7;

$sr = 48;
$bs = 4;

$row = 0;

$je = $t * $sr / $bs;
$ie = $bs;
for($j = 0; $j < $je; $j++) {
  $k = (1 - $cv) / ($sr * $t);
  for($i=0;$i < $ie; $i++) {
    $cv += $k;
    $row++;
  }
  echo "$row : leg is $k, voltage is $cv \r\n";
}


