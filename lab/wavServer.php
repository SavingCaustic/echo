<?php
//exploring using a simple wav streaming server to be able to interact with note events from mock FE.
//changes from FE read from file since SESSION wont work.

//Nope. Can't get this to work, neither in FF or chrome. giving up.

// Set headers to indicate that this is an audio file
header('Content-Type: audio/wav');
header('Cache-Control: no-cache');
header('Content-Length: 0');

// Sample rate and duration of the audio
$sampleRate = 22050; // 22050 Hz
$durationSeconds = 5; // Length of the audio in seconds

// Number of samples to generate
$numSamples = $sampleRate * $durationSeconds;


// Generate the WAV header
$header = pack('A4VVA4VvvVVvvA4V', 'RIFF', 36 + 0, 'WAVE', 'fmt ', 16, 1, 1, $sampleRate, $sampleRate * 2, 2, 16, 'data', 0);
echo $header;
flush();
//die('x');
// Generate the audio data
$data = '';
for ($i = 0; $i < $numSamples; $i++) {
    // Generate a sine wave with frequency 440 Hz (A4)
    $frequency = 880; // Frequency in Hz
    $amplitude = 0.9; // Amplitude
    $value = $amplitude * sin(2 * M_PI * $frequency * $i / $sampleRate);
    
    // Convert the value to a 16-bit signed integer (PCM)
    $pcmValue = (int)($value * 32767);
    
    // Pack the PCM value into a little-endian 16-bit signed integer
    $data = pack('v', $pcmValue);
    echo $data;
    flush();
}

?>
