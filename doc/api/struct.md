# JSON-object describing settings. If it's not here, it's not available
{
    project: {
        master_tune: 440,
        time_signature: "7/4",
        name: "Money",
        racks: [
            {
                "PATCH": {
                    name:   "Coco",
                    changed: true,
                },
                "EVENTOR1": {
                    type:   "octaver",
                    enabled: true
                },
                "EVENTOR2": {
                    type:   "quantizer",
                    enabled: false
                },
                "SYNTH": {
                    type:   "subreal",
                    midi_channel: 1
                },
                "EFFECT1": {
                    type:   "delay",
                    enabled: true
                },
                "PATTERNS": [
                    {
                        slot:   "A1",
                        length: 4,
                        swing:  false,
                        time_sign: false,
                        data:   blob
                    }
                ]
                "SEQUENCE": [
                    {
                        start:1,
                        slot: "A1",
                        length:32
                    }
                ]
            }
        ],
        song: {
            //dunno if this is all really set in racks.
        }
    }
}

#based on that this file should only be read/written on project load/save - here we go..
#project.xml
<project name="Money" bpm="100" time_sign="7/4" master_tune="432">
  <racks>
    <rack slot="1" ev1="none" ev2="none" synth="none" eff1="none" eff2="none">
      <ev1 type="doubler" patch="Octaver" changed="false" />
      <synth model="subreal" patch="Draken" changed="false" data="base64enc json.">
      <eff1 type="delay" patch="trip-fade" changed="false">
      <patterns>
        <pattern slot="1" length="4" data="adsfqwerqwerqwer">
        <pattern slot="3" length="16" data="adsfqwerqwerqwer">
      </patterns>
    </rack>
  </racks>
  <sequencer>
    <track rack="1" data="234234"/>
    <tempo data="{0:120;17:105;}">
</sequencer>
</project>

#rack1.xml

#sequencer.xml

project
  name

project.json
{
    name:"Money",
    bpm:100,
    time_sign:"7/4",
    master_tune:432,
    racks: [
        {
            synth: "beatbox"
        }
    ],[
        {
            patch: "CP80",
            ev1: {
                "type":"doubler",
                "patch":"octaver"
            },
            ev2: {
                "type":"none"
            }
            synth: {
                type:"subreal",
                patch:"CP"
            }
            eff1: {
                type:"delay",
                patch:"fading-triplets"
            }
        }
    ]
}

//patterns are loaded into pattern player and pushed back on save(?).
pattern_R01_01.json
{
    length:16,(bars?)
    ticks:16*7/4*4*96=10752
    time_sign:"7/4",
    shuffle: [0,0,0]
    events: [
        [0,144,60,127],
        [0,144,69,100],
        [96,128,60,0],
        [192,128,69,0]
    ]
}

sequence_R01.json
//blocks being start, len, pattern
{
    blocks: [
        [0,16,2],
        [16,16,1]
    ]
}

sequence_tempo.json
{
    marks: [
        [0,120],
        [15,120],
        [16,105]
    ]
}
