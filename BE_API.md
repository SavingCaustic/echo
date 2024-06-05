# BACKEND API DRAFT

## device
Settings and methods for device running the app. Not song-related.

### .settings
buffer_size
device_id
background_audio

### methods
audioStart
audioStop
testLatency
probeDevices
getInfo
listSongs

## song
Song-related attributes

### .settings
tempo
time_signature
swing
tuning
name

### methods
new
save
load
export
listRacks
setMetronome

## rack
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


## synth 
???

### settings

### methods


## pattern (or rack.pattern)

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


