<?php
// File path to the audio file
$filePath = 'pattern.wav';

// Check if the file exists
if (!file_exists($filePath)) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

// Get the file size
$fileSize = filesize($filePath);
$file = fopen($filePath, 'rb');

// Define the headers
header("Content-Type: audio/wav");
//header("Content-Length: " . $fileSize);
header("Accept-Ranges: bytes");

// Handle range requests (for seeking)
if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    $range = str_replace('bytes=', '', $range);
    list($start, $end) = explode('-', $range);
    $start = intval($start);
    $end = $end ? intval($end) : $fileSize - 1;

    header("HTTP/1.1 206 Partial Content");
    header("Content-Range: bytes $start-$end/$fileSize");
    header("Content-Length: " . ($end - $start + 1));

    fseek($file, $start);
} else {
    $start = 0;
    $end = $fileSize - 1;
}

// Stream the file content
$bufferSize = 8192;
$currentPosition = $start;

while (!feof($file) && $currentPosition <= $end) {
    if ($currentPosition + $bufferSize > $end) {
        $bufferSize = $end - $currentPosition + 1;
    }

    $buffer = fread($file, $bufferSize);
    echo $buffer;
    flush();

    $currentPosition += $bufferSize;
}

fclose($file);
?>
