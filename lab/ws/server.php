<?php
$host = 'localhost';
$port = 8080;
$null = NULL;

// Create TCP/IP stream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Bind socket to the host and port
socket_bind($socket, $host, $port);

// Listen for incoming connections
socket_listen($socket);

$clients = array($socket);

echo "WebSocket server started on ws://$host:$port\n";

while (true) {
    $changed = $clients;
    socket_select($changed, $null, $null, 0);

    if (in_array($socket, $changed)) {
        $socket_new = socket_accept($socket);
        $clients[] = $socket_new;

        $header = socket_read($socket_new, 1024);

        // Parse headers from the request
        $headers = parse_request_headers($header);
        if (isset($headers['Sec-WebSocket-Key'])) {
            perform_handshake($headers, $socket_new, $host, $port);
            socket_getpeername($socket_new, $ip);
            echo "New WebSocket client connected: $ip\n";
        } else {
            echo "WebSocket handshake failed: Sec-WebSocket-Key not found in headers\n";
            socket_close($socket_new);
        }

        $key = array_search($socket, $changed);
        unset($changed[$key]);
    }

    foreach ($changed as $changed_socket) {
        $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
        if ($buf === false) {
            // Remove the client on disconnection
            $found_socket = array_search($changed_socket, $clients);
            socket_getpeername($changed_socket, $ip);
            unset($clients[$found_socket]);
            echo "WebSocket client disconnected: $ip\n";
            continue;
        }

        $received_text = unmask($buf);
        echo "Received message from WebSocket client: $received_text\n";

        // Broadcast message to all WebSocket clients
        $response_text = mask("Server said: $received_text");
        foreach ($clients as $client_socket) {
            if ($client_socket != $socket && $client_socket != $changed_socket) {
                socket_write($client_socket, $response_text, strlen($response_text));
            }
        }
    }
}

// Close the master socket
socket_close($socket);

// Function to parse HTTP request headers
function parse_request_headers($header) {
    $headers = array();
    $lines = preg_split("/\r\n/", $header);
    foreach($lines as $line) {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }
    return $headers;
}

// Function to perform the WebSocket handshake
function perform_handshake($headers, $client_conn, $host, $port) {
    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $buffer  = "HTTP/1.1 101 Switching Protocols\r\n" .
               "Upgrade: websocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Accept: $secAccept\r\n\r\n";
    socket_write($client_conn, $buffer, strlen($buffer));
}

// Function to unmask the received data
function unmask($text) {
    $length = ord($text[1]) & 127;
    if($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

// Function to encode the data before sending to the WebSocket client
function mask($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } elseif($length >= 65536) {
        $header = pack('CCNN', $b1, 127, $length);
    }
    return $header.$text;
}
?>
