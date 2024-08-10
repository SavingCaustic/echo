<?php
//this file should probably move into the src-directory.

class WavWriter {
    private $fileHandle;
    private $dataSize = 0;
    private $open;
    private $amp;
    private $sampleRate;

    public function __construct($filename,$amp = 5000, $sampleRate = 48000) {
        $this->fileHandle = fopen($filename, 'wb');
        $this->sampleRate = $sampleRate;
        $this->writeHeader();
        $this->open = true;
        $this->amp = $amp;
    }

    public function __destruct() {
        if ($this->open) $this->close();
    }

    private function writeHeader() {
        // Write WAV header
        fwrite($this->fileHandle, pack('CCCC', 0x52, 0x49, 0x46, 0x46)); // "RIFF"
        fwrite($this->fileHandle, pack('V', 0)); // Placeholder for file size
        fwrite($this->fileHandle, pack('CCCC', 0x57, 0x41, 0x56, 0x45)); // "WAVE"
        fwrite($this->fileHandle, pack('CCCC', 0x66, 0x6D, 0x74, 0x20)); // "fmt "
        fwrite($this->fileHandle, pack('V', 16)); // PCM format chunk size
        fwrite($this->fileHandle, pack('v', 1)); // PCM format
        fwrite($this->fileHandle, pack('v', 1)); // Number of channels
        fwrite($this->fileHandle, pack('V', $this->sampleRate)); // Sample rate
        fwrite($this->fileHandle, pack('V', $this->sampleRate * 2)); // Byte rate
        fwrite($this->fileHandle, pack('v', 2)); // Block align
        fwrite($this->fileHandle, pack('v', 16)); // Bits per sample
        fwrite($this->fileHandle, pack('CCCC', 0x64, 0x61, 0x74, 0x61)); // "data"
        fwrite($this->fileHandle, pack('V', 0)); // Placeholder for data size
    }

    public function append($wave) {
        $n = sizeof($wave);
        for($i=0;$i < $n;$i++) {
          $wave[$i] *= $this->amp;    //based on polyphony = 4
        }
        $data = pack("S*", ...$wave);
        fwrite($this->fileHandle, $data);
        $this->dataSize += strlen($data);
    }

    public function close() {
        // Update file size and data size
        fseek($this->fileHandle, 4);
        fwrite($this->fileHandle, pack('V', 36 + $this->dataSize)); // File size
        fseek($this->fileHandle, 40);
        fwrite($this->fileHandle, pack('V', $this->dataSize)); // Data size
        fclose($this->fileHandle);
        $this->open = false;
    }
}
