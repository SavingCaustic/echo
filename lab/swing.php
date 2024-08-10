<?php

    function calcSwungPulse($pulse, $swingCycle, $swingDepth) {
        $angle = ($pulse % $swingCycle) / $swingCycle; 
        $swing = (0.5 - cos($angle * pi()*2)*0.5) * $swingDepth * $swingCycle / 4;
        return $swing;
    }

    echo '0: ' . calcSwungPulse(00,360,0.66666) . "\r\n";
    echo '90: ' . calcSwungPulse(90,360,0.66666) . "\r\n";
    echo '180: ' . calcSwungPulse(180,360,0.66666) . "\r\n";
    echo '270: ' . calcSwungPulse(270,360,0.66666) . "\r\n";
