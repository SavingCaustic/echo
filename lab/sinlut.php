<?php

class SinLUT {
    private static $LUT_SIZE = 10000;
    private static $lut = [];
    private static $DEG_TO_RAD = M_PI / 180.0;
    private static $initialized = false;

    // Initialize the LUT
    public static function initialize() {
        if (!self::$initialized) {
            for ($i = 0; $i < self::$LUT_SIZE; ++$i) {
                $angle = ($i * 90.0 / (self::$LUT_SIZE - 1)) * self::$DEG_TO_RAD;
                self::$lut[$i] = sin($angle);
            }
            self::$initialized = true;
        }
    }

    // Function to get sine from LUT
    public static function sinLUTFunction($angle) {
        $angle = fmod($angle, 360.0); // Normalize angle to [0, 360)
        if ($angle < 0) $angle += 360.0;

        $negate = false;
        if ($angle > 180.0) {
            $angle -= 180.0;
            $negate = !$negate;
        }
        if ($angle > 90.0) {
            $angle = 180.0 - $angle;
        }

        $index = (int)($angle * (self::$LUT_SIZE - 1) / 90.0);
        $result = self::$lut[$index];
        return $negate ? -$result : $result;
    }
}

// Initialize LUT
SinLUT::initialize();

// Benchmarking function
function benchmark($func, $angles, $name) {
    $start = microtime(true);
    foreach ($angles as $angle) {
        $result = $func($angle);
    }
    $end = microtime(true);
    $elapsed = $end - $start;
    echo $name . " took " . $elapsed . " seconds.\n";
}

// Generate random angles
$angles = [];
for ($i = 0; $i < 1000000; ++$i) {
    $angles[] = mt_rand() / mt_getrandmax() * 360.0;
}

// Benchmark standard sin function
benchmark('sin', $angles, "Standard sin");

// Benchmark LUT-based sin function
benchmark(['SinLUT', 'sinLUTFunction'], $angles, "LUT sin");

?>
