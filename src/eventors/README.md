## Eventors (Event-processors)

Processes incoming midi-signal (or self-conained).
Cares about clock but neccesarily not start/stop.

=> midi in => eventor => synth => 

An eventor could of course listen to audio, mobile sensors etc.
But it's minimum implementation is midi-in & -out.

Hard to say about noteOn etc. Rather the midi stream.
It can not alter the main midi stream but the stream to the synth in the rack.
