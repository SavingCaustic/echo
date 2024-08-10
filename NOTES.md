# theplayhead
(saving caustic)

## Semantics
**Frame**
A chunck of 64 samples, creating a time of 64/48000 sec in realtime. Frames is what drives
all timing forward.

**Pulse**
Pulse is a float between 0-95 (1/8th based on PPQN 192). Pulse is increased by every frame,
and in relation to tempo, sets the beat as the pulse counter overflows. Pulse is always without swing.

**Tick**
Tick is a high resolution (192 PPQN) measure to time recorded events. Ticks may be swung, either
through standard swing or rack custom swing settings.

**Clock** 
Clock is standard Midi 24 PPQN, driving events, effects and sending midi clock out. It may be swung too.



## Main app
Starts background-service and shows a web-view for UI-rendering.

## Test app
Similar to main app but without any web-view. Contains a prompt
where test-user may send frontend actions and other commands as json-messages.
Possibly, these messages are sent directly to the MessageParser, if it can be reached from here?
If not, messages are sent through the web socket server using (Protocol JSON-RPC 2.0)

## Frontend (uesr-interface)
Web-based, driven by vue.js and probably pinia
Comunicates with background service using http and websockets.
http are probably GET only. 
ws uses probably JSON-RPC 2.0

## Background service
Main purpose of background service is to render audio playback with low latency.
So the class has a method tied to portaudio or other low-level audio service.
This method is the starting point for all racks rendering.
So all playback is actually timedriven by this interrupt and the samplingfrequency.
(That inspired the name of the app. The playhead is where magnetism becomes audio
and it's indeed what sets the tone.  

However, the backroundservice also has a main-routine, that may be halted when
the rendering is taken place. The actions in this main-loop are of lower priority
and if possible, execution should be paused when interrupt routine is taking place,
(which may be complicated on multicore cpus).

## Low-priority actions
* Perform file read-ahead so the Playhead never have to wait for file i/o.
* Perform file write buffering (audio recording) so.. (likewise)
* Poll incoming messages on websocket server.
* Send messages on websocket based on current view.
* Various helth control. (disk space etc)
 
MessageReciever
The message recivers recieve messages. 
The message recievers translates midi 0-127 ranges to synth parameter range which may be 0-5000.
There are standard methods and custom methods.


Standard methods
setInt("currentRack",1)

setInt("currentRack.synthModel","subsynth"
** THE VALUE currentRack.synth has to be different from the object surrentRack.synth.
setStr('cr.s.OSC1_WF',3)
currentRack.currentSynth.setInt (name, val) 
currentRack.currentSynth.setInt (name, val) 

setFloat (name, val)
setStr (name, strVal)

Custom methods, for editors
currentRack.currentPattern.notesMove(timeShift,pitchShift,notes[])
notesDelete(notes[])




