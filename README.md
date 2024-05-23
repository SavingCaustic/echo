# echo
DSP breadboarding using PHP with goal to create a mobile DAW, similar to Caustic.

## Current tasks
* Testing event processors
* Thinking about pattern playback vs eventprocessor-timing.
  ) Maybe play is always on but play listens to pattern events.
  ) dunno. Or we should have a rackTick to control eventprocessors.

## Main objectives
* Preparing structure for upcoming C-project
* Exploring DSP filtering etc.
* Mocking UI of final app.

## Roadmap, (divided by milestones)
This roadmap **excludes** actions in JUCE or other C platform.
And remember, no work and all play folks! :)

* Push project to github (done)
* Get a drum machine in order
* Have a catchy beat with two tracks playing by rendering.
---
* Create example of event-processor-effect.
* Raise PPQN to 96 or similar. (done)
* Swing implementation (done).
* Swing testing. Add quarter-note swing.
* Stereo processing - how? Interleaved all the way?
* Stabilize code.
---
* Simulate audio in using wav-file reading.
* "Synth" that can record or not record (but always effect process) audio in.
---
* XML frontend rendering help tool
* Hack to play with settings for synths and effects using /www
---
* FE dialogs for pattern & sample editing. XML based visuals.


## Requirements
To run project, have a PHP-cli installation, > PHP8.

* Test scripts are run from console
* The web-based synth-previewer works best by starting the built in server like:

 ~/echo/www # php -S 127.0.0.1:8080

## Remember
* Currently, everything is subject to change.
 
![image info](./overview.svg)
