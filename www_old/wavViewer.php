<?php
//get the latest wav file and show as png.

class WavViewer {
    private $wavFile;
    private $imageWidth;
    private $imageHeight;
    private $backgroundColor;
    private $waveformColor;

    public function __construct($wavFile, $imageWidth = 1024, $imageHeight = 400, $backgroundColor = [17, 24, 17], $waveformColor = [40, 200, 40]) {
        $this->wavFile = $wavFile;
        $this->imageWidth = $imageWidth;
        $this->imageHeight = $imageHeight;
        $this->backgroundColor = $backgroundColor;
        $this->waveformColor = $waveformColor;
    }

    public function generatePlot($outputFile = null) {
        // Read WAV file
        $wavData = file_get_contents($this->wavFile);
        if (!$wavData) {
            throw new Exception("Failed to read WAV file.");
        }

        // Parse WAV data
        $format = unpack("C4chunkID/VchunkSize/C4format/C4subchunk1ID/Vsubchunk1Size/vaudioFormat/vnumChannels/VsampleRate/VbyteRate/vblockAlign/vbitsPerSample/C4subchunk2ID/Vsubchunk2Size", $wavData);
        $dataStart = strpos($wavData, "data") + 8;
        $wavData = substr($wavData, $dataStart);

        // Prepare image
        $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);
        $backgroundColor = imagecolorallocate($image, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
        $waveformColor = imagecolorallocate($image, $this->waveformColor[0], $this->waveformColor[1], $this->waveformColor[2]);
        $gridColor = imagecolorallocate($image, 50, 50, 50);
        imagefilledrectangle($image, 0, 0, $this->imageWidth, $this->imageHeight, $backgroundColor);

        // Generate waveform
        $numSamples = strlen($wavData) / ($format['bitsPerSample'] / 8);

        imageline($image, 0, 100, 1024, 100, $gridColor);
        imageline($image, 0, 200, 1024, 200, $gridColor);
        imageline($image, 0, 300, 1024, 300, $gridColor);

        $oldX = 0;
        $oldY = 0;
        for ($x = 0; $x < $this->imageWidth; $x++) {
            $sample = unpack("s", substr($wavData, $x * 2, 2));
            //if ($x == 100) die(serialize($sample));
            $y = $sample[1] / 150; 
            imageline($image, $oldX, 200 - $oldY, $x,200 - $y, $waveformColor);
            $oldX = $x;
            $oldY = $y;
            //imagesetpixel($image, $x, $y + 200, $waveformColor);
        }

        if (is_null($outputFile)) {
            header('content-type: image/png');
            imagepng($image);
        } else {
            // Save image
            imagepng($image, $outputFile);
            imagedestroy($image);
        }
    }
}
