<?php
/*
1) identify rack and synth
2) convert 0-127 to real val
3) contact masterPlayer and update setting
4) return val
*/
if (array_key_exists('rack', $_GET)) {
    //rack setting..
    $rack = $_GET['rack'];
    $rackModule = $_GET['rack_module'];
    $moduleName = $_GET['module_name'];
    $$ccName = $_GET['name'];
    $ccValue = $_GET['value'];
    if (in_array($moduleName,array('synth','effect','eventor'))) {
        //the conversion code should maybe be known to the ws itself?
        //the rack knows, it's what makes sense.
        $rack->cc2param($moduleName, $ccName, $value);
    } else {
        //pattern?
    }
} else {
    //something else..
}
$rack = $_GET['rack'];