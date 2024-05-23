<?php
header("Content-Type: audio/wav");
header("Transfer-Encoding: chunked");
header("Cache-Control: no-cache, must-revalidate");

// Send the WAV header
function sendWavHeader() {
    $sampleRate = 44100;  // Sample rate (44.1kHz)
    $numChannels = 1;     // Mono
    $bitsPerSample = 16;  // 16-bit
    $byteRate = $sampleRate * $numChannels * $bitsPerSample / 8;
    $blockAlign = $numChannels * $bitsPerSample / 8;

    // RIFF header
    $header = pack('N4', 0x52494646, 36 + 44, 0x57415645, 0x666D7420);  // "RIFF", file size (dummy), "WAVE", "fmt "
    $header .= pack('V2n4', 16, 1, $numChannels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample);
    $header .= pack('N2', 0x64617461, 0x7fffffff); // "data", large dummy size for streaming

    echo $header;
    flush();
}

// Simulate generating 1 second of silent audio
function generateAudioChunk() {
    $numSamples = 44100; // 1 second of audio at 44.1kHz
    return str_repeat(pack('v', 0), $numSamples); // 16-bit PCM samples
}

// Send WAV header
sendWavHeader();

// Stream audio data in real-time
while (true) {
    $chunk = generateAudioChunk();
    $chunkLength = strlen($chunk);

    // Send the length of the chunk in hexadecimal followed by \r\n
    echo dechex($chunkLength) . "\r\n";
    // Send the chunk data followed by \r\n
    echo $chunk . "\r\n";
    // Flush the output buffer
    flush();

    // Sleep to simulate real-time audio data generation
    usleep(1000000); // Adjust timing based on actual data generation rate
}

// This is an infinite loop, so "0\r\n\r\n" to end the chunked transfer will never be reached
?>
