<?php
if (true) { //$_SERVER['REQUEST_URI'] == '/audio') {
    header('Content-Type: audio/mpeg');

    // Call the function to generate audio stream
    generateAudioStream();
    exit;
}

function generateAudioStream() {
    // Your audio generation logic goes here
    $sampleRate = 44100;
    $duration = 10; // 10 seconds of audio
    $frequency = 440; // A4 note

    $amplitude = 32767;
    for ($i = 0; $i < $sampleRate * $duration; $i++) {
        $sample = $amplitude * sin(2 * pi() * $frequency * $i / $sampleRate);
        echo pack('v', $sample); // 'v' is the format for 16-bit PCM audio
    }
}

// Handle 404 Not Found
header("HTTP/1.1 404 Not Found");
echo "404 Not Found";
?>
