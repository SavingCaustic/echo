# What?

One possible route, but not the first option.. 
Having a web-based UI driven by websockets between FE & BE.

Currently this directory doesn't do anything useful, and doesn't behave like we want it to do.

## Usage

start server from terminal, as console applcation (in this directory)

   > php server.php

A ws-server is now running on port 7070.

A regular web-server is need to initiate communiction so open another terminal window and run

   > php -S 127.0.0.1:8080

From a browser of choice, request http://127.0.0.1/client.php

