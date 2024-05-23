<?php

//this class *could* be used to generate wav-file stream and act on commands.
//below actually works. Start script from cli. It will accept http-requests *with* parallel execution.

class HttpServer {
    private $address;
    private $port;
    private $serverSocket;
    private $clients = [];
    private $callbacks = [];

    public function __construct($address, $port) {
        $this->address = $address;
        $this->port = $port;
    }

    public function on($event, callable $callback) {
        $this->callbacks[$event] = $callback;
    }

    public function start() {
        $this->serverSocket = stream_socket_server("tcp://{$this->address}:{$this->port}", $errno, $errstr);

        if (!$this->serverSocket) {
            die("Error: $errstr ($errno)\n");
        }

        stream_set_blocking($this->serverSocket, 0);

        echo "Server listening on {$this->address}:{$this->port}\n";

        while (true) {
            $this->loop();
        }
    }

    private function loop() {
        $readSockets = $this->clients;
        $readSockets[] = $this->serverSocket;

        if (stream_select($readSockets, $write, $except, 0,1000) > 0) {
            if (in_array($this->serverSocket, $readSockets)) {
                $this->acceptClient();
                unset($readSockets[array_search($this->serverSocket, $readSockets)]);
            }

            foreach ($readSockets as $client) {
                $this->handleClient($client);
            }
        }
        echo 'monkey';
        usleep(1000);
    }

    private function acceptClient() {
        $client = stream_socket_accept($this->serverSocket);
        if ($client) {
            stream_set_blocking($client, 0);
            $this->clients[] = $client;
            echo "New connection accepted\n";

            if (isset($this->callbacks['connect'])) {
                call_user_func($this->callbacks['connect'], $client);
            }
        }
    }

    private function handleClient($client) {
        $data = fread($client, 1024);

        if ($data === false || $data === "") {
            $this->disconnectClient($client);
            return;
        }

        if (isset($this->callbacks['data'])) {
            call_user_func($this->callbacks['data'], $client, $data);
        }

        // Close the client connection after handling
        $this->disconnectClient($client);
    }

    private function disconnectClient($client) {
        fclose($client);
        unset($this->clients[array_search($client, $this->clients)]);
        echo "Client disconnected\n";

        if (isset($this->callbacks['disconnect'])) {
            call_user_func($this->callbacks['disconnect'], $client);
        }
    }
}

// Instantiate the server
$server = new HttpServer('127.0.0.1', 8080);

// Define callback for new connections
$server->on('connect', function ($client) {
    echo "New client connected\n";
});

// Define callback for incoming data
$server->on('data', function ($client, $data) {
    echo "Received request:\n$data\n";

    // Simple HTTP response
    $responseBody = "Hello, World!\n";
    $response = "HTTP/1.1 200 OK\r\n";
    $response .= "Content-Type: text/plain\r\n";
    $response .= "Content-Length: " . strlen($responseBody) . "\r\n";
    $response .= "\r\n";
    $response .= $responseBody;

    // Write the response to the client
    fwrite($client, $response);
});

// Define callback for client disconnections
$server->on('disconnect', function ($client) {
    echo "Client disconnected\n";
});

// Start the server
$server->start();
