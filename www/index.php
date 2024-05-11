<?php
define('crlf',"\r\n");
session_start();

$cmd = @$_REQUEST['cmd'] . '';
switch($cmd) {
  case 'reset':
    $_SESSION = array();
    break;
}

//$_SESSION = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  //update session data, and that's like it.
  $_SESSION = $_POST;
  //request rendering of wav-file..
  require('renderer.php');
}
//how should all available parameters be presented? From synth controller right?
?>
<!DOCTYPE html>
<html>
<head>
<style>
body, td,input {
  background-color:#111811;
  color:#CCC;
  font-size:16px;
  font-family: Courier;
}

h2 {
  margin:0px;
}

a {
  color:#f8c;
  text-decoration: none;
}
</style>
</head>
<body>
<form method="post" action="index.php">
<table><tr><td><h2>Synth</h2></td>
<td><select name="synth">
  <option>select..</option>
<?php
$synths = array('subcult','waveform');
foreach($synths as $synth) {
  $sel = ($synth == @$_SESSION['synth']) ? ' selected="selected"' : '';
  echo '<option ' . $sel . '>' . $synth . '</option>' . crlf;
}
?>
</select></td>
<td><a href="?cmd=reset">reset..</a></td>
</tr></table>

<h2>Controls</h2>
<table>
<?php 
$cols = 6;
if ($_SESSION['synth'] != '') {
  $path = '../assets/synths/' . $_SESSION['synth'] . '/defaults.json';
  $controls = json_decode(file_get_contents($path),true);
} else {
  //no synth loaded so iterate over nothing..
  $controls = array();
}
if (!is_array($controls)) $controls = array();
$keys = array_keys($controls);
for($i=0;$i<sizeof($keys);$i++) {
  $s = ($i % $cols == 0) ? '<tr>' : '';
  //override with changed value..
  $val = (isset($_SESSION[$keys[$i]])) ? $_SESSION[$keys[$i]] : $controls[$keys[$i]];
  //die(serialize($_SESSION));
  //$val = $controls[$keys[$i]];
  //die(serialize($keys[$i]));
  $s .= '<td>' . $keys[$i] . '</td><td><input type="text" name="' . $keys[$i] . '" size="6" value="' . $val . '" /></td>';
  if ($i % $cols < ($cols - 1)) {
    $s .= '<td>&nbsp;</td>';
  } else {
    $s .= '</tr>';
  }
  echo $s;
}
//trust html to close incomple table.
echo '</table>' . crlf;

$sequence = @$_SESSION['sequence'] . '';
if (strlen($sequence)==0) {
  $sequence = '49x10,Px10,69+72+76x10,Px10';
}
?>
<h2>Play sequence</h2>
<input type="text" size="50" name="sequence" value="<?php echo $sequence;?>" />
&nbsp; <input type="submit" value="Render">
</form>
<hr>
<audio autoplay controls>
  <source src="renderer.wav?ts=<?=time()?>" type="audio/wav">
</audio>
<hr/>
<img src="oscilloscope.php" width="1000" />
</body>
</html>
