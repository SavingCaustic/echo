# What?

## Web based UI

Exploring if web based UI at all is a viable option.
Start php server as above but request: http://127.0.0.1/test.php

UPDATE: Trashing idea of using web-sockets. For echo, polling every 100mS is just fine.
What would be asyncronous is automation and VU-meter.

Or just choose the JUCE UI solution with its limitations.

## Trashed Web-sockets server
Below to be remved:

One possible route, but not the first option.. 
Having a web-based UI driven by websockets between FE & BE.
Currently this directory doesn't do anything useful, just for WS experiments.

## Usage

start server from terminal, as console applcation (in this directory)

   > php server.php

A ws-server is now running on port 7070.

A regular web-server is need to initiate communiction so open another terminal window and run

   > php -S 127.0.0.1:8080

From a browser of choice, request http://127.0.0.1/client.php

