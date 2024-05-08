# PHP environment aiming at
* for testing DSP-functions,
* step-by-step builing an app-environment that's similar to a lost friend
* No dependencies, (no composer etc)
* GD required for www-bench, that's all.

the aim is not:
* events engine
* building frontend using CSS / HTML5.


## Outline for improved app architecture
    ```
APP
  Rack (patch?)
    MidiProcessor
    AudioProcessor
    Synth
      Voice (polyphony)
    Effect1  
    Effect2
  Master
    TrackMix
    Delay
    Reverb
    Limiter
    ..

    ```


## Super-alpha

* No working www-bench
* Just a few test scripts
* 

