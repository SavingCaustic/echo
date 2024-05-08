<?php

class wavplayerModel {
    var $samples;
    var $samplePtr;


    function __construct($dspCore) {
        $this->dspCore = &$dspCore;
        $this->initSettings();
        $this->pushSettings();
    }
    
    public function initSettings() {
      $this->settings = array(
        'WAVEFORM' => 'wavplayer_in.wav'
      );
      file_put_contents(__DIR__ . '/defaults.json',json_encode($this->settings));
    }

    public function pushSettings() {
    }

    function pushSetting($setting) {
    }

    function noteOn($note, $vel) {
        //this shitty code was written by chat-gtp, don't trust it.
        //for now, just one sample.
        $this->fp = fopen($this->settings['WAVEFORM'],'rb');
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
        $bits_per_sample = $data['bits_per_sample'];
        $data_size = $data['data_size'];

        $channels = $data['channels'];
        $bytes_per_sample = $bits_per_sample / 8;
        $number_of_samples = $data_size / ($bytes_per_sample * $channels);
        $this->sampleCnt = $number_of_samples;
    }

    function noteOff($note, $vel) {
        // Close the file
        fclose($this->fp);

    }

    function renderNextBlock() {
        // Read the data (samples)
        $bufferSize = $this->dspCore->rackRenderSize;
        $samples = [];
        for ($i = 0; $i < $bufferSize; $i++) {
            if ($this->samplePtr <= $this->sampleCnt) {
                // Read a 16-bit signed integer (2 bytes)
                $sampleRaw = fread($this->fp, 2);
                $sample = unpack("s", $sampleRaw)[1];
                $samples[$i] = $sample / 32768.0;
                $this->samplePtr++;
            } else {
                $samples[$i] = 0;
            }    
            //$samples[$i] = 0;
        }
        return $this->buffer = $samples;
    }

}