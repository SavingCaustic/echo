<?php
// Set the image width and height
$width = 1024;
$height = 640;

// Create the image
$image = imagecreatetruecolor($width, $height);

// Define background and line colors
$backgroundColor = imagecolorallocate($image, 0, 0, 0); // black background
$lineColor = imagecolorallocate($image, 255, 255, 255); // white waveform

// Fill the image with the background color
imagefill($image, 0, 0, $backgroundColor);

// Example array of overtone amplitudes (10 float values)
$overtoneAmplitudes = [1.0, 0.5, 0.3, 0.2, 0.1, 0.1, 0.05, 0.05, 0.02, 0.01];

// Frequency base for the fundamental tone (can adjust this)
$baseFrequency = 440; // Hz (A4 note)

// One cycle of the fundamental should fit across the full width of the image
$fundamentalPeriod = $width / $baseFrequency; // Period of fundamental in pixels (one cycle)

// Create the waveform by summing sinusoidal components
$points = [];
for ($x = 0; $x < $width; $x++) {
    $y = 0; // Start at the center vertical axis

    // Add each overtone to the waveform
    foreach ($overtoneAmplitudes as $n => $amplitude) {
        $frequency = $baseFrequency * ($n + 1); // Calculate frequency for the overtone
        // Scale the sine wave to fit one cycle across the full width of the image
        $y += $amplitude * sin(2 * M_PI * $frequency * $x / $fundamentalPeriod); // Use fundamental period for scaling
    }

    // Normalize the result: Ensure the waveform stays within the image height
    $y = ($y * ($height / 4)); // Increase amplitude scaling
    $y = $height / 2 + $y; // Center the waveform vertically

    // Clip the value to ensure it's within the height of the image
    $y = max(0, min($height - 1, $y)); 

    // Add the point to the array of waveform points
    $points[] = $x;
    $points[] = $y;
}

// Draw the waveform
imagepolygon($image, $points, count($points) / 2, $lineColor);

// Output the image
header('Content-Type: image/png');
imagepng($image);

// Clean up
imagedestroy($image);
?>
