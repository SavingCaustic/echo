how to run?

app->transportState:
stopped:
* do not run sequences
* render synths (no tick needed - see below)
* run clock for lfo etc (no tick needed - see below)

playing:
* pattern mode - loop over selected pattern
* song mode - load different patterns as we go..

since we have lfo / delay etc with respect to midi-clocks as:
2,3,4,6,8,12,16,24,32,48,64,96
* Delay effect don't bother with ticks, calc samples from tempo.
  * 120bpm, 8t => 2/12 => 167 mS = 7530 samples (fifo-size)
  * 150bpm, 16 => 100 mS => 4410 samples
* Does lfo bother with ticks? no.. but nor sample-frequency.
  Hz = 4/4 & 120pbm => 4ths = 2Hz, 16t = 12Hz
  130bpm = 

we just need some awareness for synths when tempo changes,
at any renderblock, they should get the tempo. Possibly, tempo-change
should set dsp-optimized copies as samplesPerTick, TickHz
120 pbm => TickHz = 48, samplesPerTick = 918.75
150 bpm => TickHz = 60, samplesPerTick = 735 / 128 => 5.74
151 bpm => TickHz = 60.4, samplesPerTick = 730.... Maybe no float in samplesPerTick?

Ok, cool lfo & delay don't listen to ticks. Only tempo-based fifo-size and TickHz based on tempo. Cool.


Where is pattern? Is it in the rack? Yes or no?
Well it's in the song or in the project. So..

Project
  Racks
    Patches? Well when we save the rack, we don't save the sequence. duh.
    Or only the loaded pattern? If so, pattern = &pattern, nextPattern = &somePattern
    Patterns(?)
  Mixter
  Song  
    Patterns(?)


