# REST-API
Basic ol' rest for first iteration as well as some stuff that might be kept here.

## /device/settings

#### GET /device_settings
Get all the devices settings from the JSON-device file

#### PATCH /device_settings
Will iterate over submitted settnings.

### actions

#### POST /device/stopService
#### POST /device/startService
#### POST /device/stopAudio
#### POST /device/startAudio


## /songs

#### GET /songs
List of all songs on device, by default sorted on desc mtime

Key for song, is that int or char? If we keep directory structure maybe char.
If sqlite: int

### actions

POST /songs/newSong

Creates a new song

POST /songs/(songname)/loadSong

## /project_settings

#### GET /project_settings 

Return settings for the current project

