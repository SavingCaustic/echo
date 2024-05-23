<?php

class WavReader {
    //could probably do quite much in the end, scrubbing etc.
    //minimal now.

    function wav2buffer($wavFile) {
        //open a wav-file, verify it's not to big and load it into buffer-pointer.
        $this->fp = fopen($wavFile,'rb');
        $this->samplePtr = 0;
        // Read the header
        $file = &$this->fp;
/*      Positions   Sample Value         Description
        1 - 4       "RIFF"               Marks the file as a riff file. Characters are each 1. byte long.
        5 - 8       File size (integer)  Size of the overall file - 8 bytes, in bytes (32-bit integer). Typically, you'd fill this in after creation.
        9 -12       "WAVE"               File Type Header. For our purposes, it always equals "WAVE".
        13-16       "fmt "               Format chunk marker. Includes trailing null
        17-20       16                   Length of format data as listed above
        21-22       1                    Type of format (1 is PCM) - 2 byte integer
        23-24       2                    Number of Channels - 2 byte integer
        25-28       44100                Sample Rate - 32 bit integer. Common values are 44100 (CD), 48000 (DAT). Sample Rate = Number of Samples per second, or Hertz.
        29-32       176400               (Sample Rate * BitsPerSample * Channels) / 8.
        33-34       4                    (BitsPerSample * Channels) / 8.1 - 8 bit mono2 - 8 bit stereo/16 bit mono4 - 16 bit stereo
        35-36       16                   Bits per sample
        37-40       "data"               "data" chunk header. Marks the beginning of the data section.
        41-44       File size (data)     Size of the data section, i.e. file size - 44 bytes header.
        */
        // Extract information from the header
        $header = fread($file, 44); // WAV files typically have a 44-byte header
        $data = unpack('Vlength/vformat/vchannels/Vsample_rate/Vbyte_rate/vblock_align/vbits_per_sample/C4cheader/Vdata_size', substr($header,16));
        $sample_rate = $data['sample_rate'];
        //only allow 44.1 ?
        $bits_per_sample = $data['bits_per_sample'];
        $data_size = $data['data_size'];
        if($data_size > 100000) die('nope, file too big');
        $channels = $data['channels'];
        //for now only allow mono..
        $bytes_per_sample = $bits_per_sample / 8;
        $number_of_samples = $data_size / ($bytes_per_sample * $channels);
        $this->sampleCnt = $number_of_samples;
        $data = fread($file, $this->sampleCnt*2);
        $integers = unpack('s*', $data);
        //for some stupid reason, unpack starts at [1], so fix that with a slow iteration now.
        $floats = array();
        for($i=1;$i<=$number_of_samples;$i++) {
            $floats[$i-1] = $integers[$i] / 32768;
        }
        return $floats;
    }
}