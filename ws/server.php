<?php

// Create WebSocket server
$server = new WebSocketServer("localhost", 7070);

class WebSocketServer {
    private $server;
    private $clients = [];
    private $tick;
    private $lastTimeSent = 0;

    public function __construct($host, $port) {
        // Create WebSocket server
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $host, $port);
        socket_listen($this->server);
        $this->tick = $this->getTick();
        echo "WebSocket server started on {$host}:{$port}\n";

        // Accept clients
        while (true) {
            echo "Server loop \n";
            $client = socket_accept($this->server);
            if ($client !== false) {
                $this->performHandshake($client);
                $this->clients[] = $client;
                echo "New client connected\n";
                $this->sendTimeToClient($client);
            }

            // Handle client messages
            $this->handleMessages($client);

        }
    }

    private function sendTimeToClient($client) {
        $currentTime = date("H:i:s");
        $this->sendMessage($client, 'time: ' . $currentTime);
    }

    private function getTick() {
      return time() / 4;
    }

    private function performHandshake($client) {
        $headers = [];
        $request = socket_read($client, 1024);
        preg_match_all('/\n(.*?): (.*?)\r/', $request, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $headers[$match[1]] = $match[2];
        }
        $key = $headers['Sec-WebSocket-Key'];
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
        // Send response to client
        echo "Sending handshake response:\n$response\n";
        socket_write($client, $response);
    }

    private function handleMessages($client) {
        while (true) {
            $data = $this->recv($client);
            if ($data === false) {
                // Client disconnected
                $index = array_search($client, $this->clients);
                if ($index !== false) {
                    array_splice($this->clients, $index, 1);
                }
                socket_close($client);
                echo "Client disconnected\n";
                break;
            } else {
                // Handle received data
                // For simplicity, just echo back the received message
                // Handle received data
                echo "Received message from client: $data\n";
                 $this->sendMessage($client, $data);
                 sleep(1);
                 $this->sendTimeToClient($client);
            }
        }
    }
    
    private function sendMessage($client, $message) {
        $opcode = 0x1; // Text frame
        $payloadLength = strlen($message);
        $frame = chr(0x80 | $opcode); // FIN bit set + opcode
        if ($payloadLength <= 125) {
            $frame .= chr($payloadLength);
        } elseif ($payloadLength <= 65535) {
            $frame .= chr(126) . pack('n', $payloadLength);
        } else {
            $frame .= chr(127) . pack('NN', 0, $payloadLength);
        }
        $frame .= $message;
        socket_write($client, $frame);
    }
    
    private function recv($client) {
        $data = socket_read($client, 2048);
        $bytes = strlen($data);
        if ($bytes === 0) {
            // Client disconnected
            return false;
        } else {
            $opcode = ord($data[0]) & 0x0F;
            if ($opcode !== 0x1) {
                // Opcode must be 0x1 for text frame
                return false;
            }
            $payloadLength = ord($data[1]) & 127;
            $maskOffset = 2;
            if ($payloadLength === 126) {
                $payloadLength = unpack('n', substr($data, 2, 2))[1];
                $maskOffset = 4;
            } elseif ($payloadLength === 127) {
                $payloadLength = unpack('J', substr($data, 2, 8))[1];
                $maskOffset = 10;
            }
            $mask = substr($data, $maskOffset, 4);
            $payloadOffset = $maskOffset + 4;
            $decodedData = '';
            for ($i = $payloadOffset, $j = 0; $i < $bytes; ++$i, ++$j) {
                $decodedData .= $data[$i] ^ $mask[$j % 4];
            }
            return $decodedData;
        }
    }

    
}

?>
