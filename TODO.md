## TODO
* Stereo processing. 
    * I subsynth, en voice är mono men kan placeras i stereo.
    * I beatnik, stöd för stereosamples eller bara pan?

* Generell volymlogk, omvandla db till % (med cache)

* I beatnik, Cacha float-konverterad wav. 

* FE-builder. Pusha settings.json etc till rätt kataloger.

* CC-konverter. Utifrån parameter min,max,e, omvandla mellan CC och värde.

* Hur göra mock av "superParent" ?

* Kan vi köra DSP-frequency på 24kHz och bufferFrequency på 48?
    * Oversampling vid output. Kanske inte fler alternativ än 24.
    * Ev output också 24(?)
    * Ev skulle DSP-frequency vara beroende av synth. En sampleplayer kan väl mata på..

* Hur mocka inspelning

* Hur mocka buffring av samples (ej beatnik)

* Göra en Fm-synth?

* Få ordning på PEG i subsynth?

* Midi in & out. Tidstämplar m.m. 
    * Är midi-in vid rendering good enough?
    * ska vi köra på timer 1mS och skippa vid render?
    
* API-dokumentation. Var? Format osv. Stort jobb.. Swagger?



## BE DONE
* Push project to github (done)
* Get a drum machine in order (prototype ok)
* Have a catchy beat with two tracks playing by rendering. (done)
---
* Create example of event-processor-effect. (done)
* Raise PPQN to 96 or similar. (done)
* Swing implementation (done).
* Swing testing. Add quarter-note swing. (done)
---


## FE DONE:
* smaller knobs for peg etc.
* vertical slider
