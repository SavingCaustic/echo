<?php

class BBDSimulation {
    private $fifoBuffer;
    private $writeIndex;
    private $readIndex;
    private $clockFreq;
    private $sampleRate;
    private $bufferSize;
    private $fractionalIndex;

    public function __construct($bufferSize, $initialClockFreq) {
        $this->fifoBuffer = array_fill(0, $bufferSize, 0.0);
        $this->writeIndex = 0;
        $this->readIndex = 0;
        $this->clockFreq = $initialClockFreq;
        $this->sampleRate = 44100; // Assuming a sample rate of 44.1 kHz
        $this->bufferSize = $bufferSize;
        $this->fractionalIndex = 0.0;
    }

    public function setClockFrequency($newClockFreq) {
        $this->clockFreq = $newClockFreq;
    }

    private function cubicInterpolation($x0, $x1, $x2, $x3, $t) {
        // Cubic interpolation formula
        $a = (-0.5 * $x0) + (1.5 * $x1) - (1.5 * $x2) + (0.5 * $x3);
        $b = $x0 - (2.5 * $x1) + (2 * $x2) - (0.5 * $x3);
        $c = (-0.5 * $x0) + (0.5 * $x2);
        $d = $x1;
        return $a * $t*$t*$t + $b * $t*$t + $c * $t + $d;
        //return $a * pow($t, 3) + $b * pow($t, 2) + $c * $t + $d;
    }

    public function processChunk($inputSamples) {
        $chunkSize = count($inputSamples);
        $outputSamples = [];

        for ($i = 0; $i < $chunkSize; $i++) {
            $inputSample = $inputSamples[$i];
            $samplesPerInput = $this->clockFreq / $this->sampleRate;

            while ($this->fractionalIndex < 1.0) {
                // Determine the indices for interpolation
                $index0 = ($this->writeIndex - 3 + $this->bufferSize) % $this->bufferSize;
                $index1 = ($this->writeIndex - 2 + $this->bufferSize) % $this->bufferSize;
                $index2 = ($this->writeIndex - 1 + $this->bufferSize) % $this->bufferSize;
                $index3 = $this->writeIndex % $this->bufferSize;

                // Read the four samples for interpolation
                $x0 = $this->fifoBuffer[$index0];
                $x1 = $this->fifoBuffer[$index1];
                $x2 = $this->fifoBuffer[$index2];
                $x3 = $inputSample; // Use current input sample for the fourth point

                // Perform cubic interpolation
                $interpolatedSample = $this->cubicInterpolation($x0, $x1, $x2, $x3, $this->fractionalIndex);

                // Write the interpolated sample to the FIFO buffer
                $this->fifoBuffer[$this->writeIndex] = $interpolatedSample;
                $this->writeIndex = ($this->writeIndex + 1) % $this->bufferSize;

                // Increment fractional index by the inverse of the samples per input ratio
                $this->fractionalIndex += 1.0 / $samplesPerInput;
            }

            // Reset fractional index for the next input sample
            $this->fractionalIndex -= 1.0;

            // Read the output sample from the FIFO buffer at the original sample rate
            $outputSample = $this->fifoBuffer[$this->readIndex];
            $this->readIndex = ($this->readIndex + 1) % $this->bufferSize;

            $outputSamples[] = $outputSample;
        }

        return $outputSamples;
    }
}

// Example usage
$bufferSize = 512; // Example buffer size
$initialClockFreq = 88200; // Initial clock frequency in Hz (2x the sample rate)
$bbdSim = new BBDSimulation($bufferSize, $initialClockFreq);

// Example LFO function
function lfo($frequency, $amplitude, $sampleRate, $time) {
    return $amplitude * sin(2 * M_PI * $frequency * $time);
}

// Process incoming samples in chunks
$inputSignal = array_fill(0, 1024, 1.0); // Example input signal (filled with ones for simplicity)
$outputSignal = [];
$chunkSize = 256; // Process in chunks of 256 samples
$lfoFreq = 0.5; // LFO frequency in Hz
$lfoAmp = 44100; // LFO amplitude (center frequency is 88200 Hz, modulation ±44100 Hz)

$chunks = array_chunk($inputSignal, $chunkSize);

foreach ($chunks as $chunk) {
    $currentTime = count($outputSignal) / 44100; // Calculate current time based on output samples
    $modulatedFreq = 88200 + lfo($lfoFreq, $lfoAmp, 44100, $currentTime);
    $bbdSim->setClockFrequency($modulatedFreq);
    $outputChunk = $bbdSim->processChunk($chunk);
    $outputSignal = array_merge($outputSignal, $outputChunk);
}

// Output signal now contains the processed samples
?>
