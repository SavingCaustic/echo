<?php
//run me from the terminal to download contents from https://oramics.github.io/

$name = @$_SERVER['argv'][1];
if (is_null($name)) die("enter name of resource to download, like lm-2\n");

$json = file_get_contents($name . '.json');
$data = json_decode($json, true);

$url = $data['samples'];
foreach($data['files'] as $file) {
  $wav = file_get_contents($url . $file);
  file_put_contents($name . '/' . $file, $wav);
}
echo "done\n";