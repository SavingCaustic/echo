<?php

class AudioWriter {
    //this class is initiated when audio-in is to be written to disk.
    //possibly also when rendering song to wav.
    //dunno really if it's floats coming in, and they're converted to ints here.

    //possiby we could use this class in test scripts?

    private int $bufferSize;
    private array $audioBuffer;
    private int $writerIX;  //from core
    private int $readerIX;  //to disk
    private bool $flushRequested;
    private int $chunkSize;
    private int $diskFullWarning;   //maybe this should be more commonly available?
    private $fileName;      //file-name to write
    private $fileHandle;

    function __construct(PlayerEngine $playerEngine, $bufferSize = 131072) {
        //bufferSize is calculated to be 128kb.
        //Then 64kB is written at a time, with a frequency of 3 bursts/sec (48kHz stereo)
        //Could be smaller units to possibly, like 32kB.
        $this->reset();
    }

    function reset() {
        $this->writerIX = 0;
        $this->readerIX = 0;
        $this->flushRequested = false;
        $this->chunkSize = 32768;
        $this->audioBuffer = array_fill(0, $this->bufferSize, 0);
    }

    function setTargetFile($fileName, $format, $stereo) {
        $this->fileName = $fileName;
        $this->fileHandle = fopen($fileName, 'w');
        $this->reset();
    }

    function writeFileHeader() {
    }

    function addData(&$data, $chunkSize) {
        //all the data should be copied to the audioBuffer.
        for ($i = 0; $i < $chunkSize; $i++) {
            $this->audioBuffer[$this->writerIX] = $data[$i];
            $this->writerIX = ($this->writerIX + 1) % $this->bufferSize;
        }
        $distance = ($this->writerIX - $this->readerIX + $this->bufferSize) % $this->bufferSize;
        if ($distance >= $this->chunkSize) {
            $this->flushRequested = true;
        }
    }

    function flushToDisk() {
        //we should support different formats right?
        //if writerIX is > 32kB ahead of reader, write and update
        if ($this->readerIX + $this->chunkSize <= $this->bufferSize) {
            // The chunk fits within the buffer bounds
            $data = array_slice($this->audioBuffer, $this->readerIX, $this->chunkSize);
        } else {
            // The chunk wraps around the end of the buffer            
            $part1 = array_slice($this->audioBuffer, $this->readerIX, $this->bufferSize - $this->readerIX);
            $part2 = array_slice($this->audioBuffer, 0, $this->chunkSize - $this->bufferSize + $this->readerIX);
            $data = array_merge($part1, $part2);
        }
        $data = implode('', $data);
        fwrite($this->fileHandle, $data);
        //now update the pointer for the reader
        $this->readerIX = ($this->readerIX + $this->chunkSize) % $this->bufferSize;
        //if we're running out of disk space we should really just close the file and quit no?
        //possibly route to a dummy, easier error code.
        $this->diskFullWarning = false;
    }
}
