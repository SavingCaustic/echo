hur ska kommuniktion funka mellan midi<>synth?

VCA_RELEASE:
midi-keyboard < 0-127 > controller < 0-5000 > synthModel

vem sparar patchen och vad sparas i den?k
klart vettigast att ha json-style:


A patch is stored as JSON
eg. OSC1_WF:SQUARE
* the controller can tell to screen "SQUARE".
* the controller feeds to synthModel: OSC1_WF => SQUARE

* patches are *not* == midi cc messages

* or should i skip "rotary switch?" doesn't matter time 0-5000 still a problem.

* controller needs to go back and forth between log

<knob name="vca_r" min="0" max="5000" curve="log">
<knob name="osc2_semi" min="-7" max="+7">
<slider name="master" label="Master" curve="log" steps="">

0.127   0-5000      0-5000
CC  >   knob    >   model 

CC = knob-rotation (not knob value)

<rotrayknob name="OSC1_WF" values="sqr,sin,tri,saw" labels="Sin,Tri,Saw,Sqr">
<knob curve="log" min="0" max="5000"> => ln(5000) / 127.
<centerknob name="PAN" min="-50" max="50" threshold="auto" center="0" >

k = ln(5000/127*4)
val = exp(k*CC)+CC*4


