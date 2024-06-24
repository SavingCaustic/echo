# BACKEND API DRAFT
Suspended. Should probably be driven by the specifications of the project.json files etc.

## GENERAL

JSON-RPC 2.0 format. See spec.


## PROJECT
Container of racks and (non-device) master settings.


### Methods
projects.list
project.new()
project.save(name)
project.open(name)
project.export(name,format)
project.setVal
project.getVal
project.setStr
project.getStr

### Attributes
.name       (str)
.bpm        (int)
.timesign   (str)
.shuffle    (int)   (-100 - 0 - 100)
.mastertune (int)   (440)


## RACK

### Methods
rack.list
rack.init   {rack:0}
rack.delete {rack:0}
rack.delPart {rack:0, part:"eventor1"}
rack.addPart {rack:0, part:"effect1", "type":"delay","patch":"fading-triplets"}
in rest that would be
POST: /racks/0/effect1/loadPatch
POST: /racks/0/effect1/drop
xml-rpc
racks.effect1.drop {rack:0}
racks.effect1.disable {rack:0}
racks.synth.load {rack:0, type:subreal, patch:"CP80"}


rack.[N].eventor1.select
//not sure about above, maybe N as arg in data, probably

### Attributes


## SYNTH
synth.select
synth.loadPatch
synth.setVal
synth.getVal



## DEVICE
Settings and methods for device running the app. Not song-related.

### Methods

### Attributes
.buffer_size            (str) (low,medium,high)
.device_id              (int)
.background_audio       (0/1)

### methods
audioStart
audioStop
testLatency

## SONG
Song-related attributes


### methods
new
save
load
export
listRacks
setMetronome

## RACK
Actions for rack

### .settings
track_number
midi_channel
name

### methods
new
edit
delete
focus


## SYNTH 
???

### settings

### methods


## PATTERN (or rack.pattern)

### methods
reset
create
get
length
swingOverride
notesAdd
notesDelete
notesQuantize
notesMove
save
compare


