<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Client</title>
</head>
<body>
    <h1>WebSocket Client</h1>
    <input type="text" id="messageInput" placeholder="Type your message...">
    <button onclick="sendMessage()">Send</button>
    <hr>
    <div id="messages"></div>

    <script>
        // Establish WebSocket connection
        const socket = new WebSocket('ws://localhost:7070');

        // Handle incoming messages
        socket.addEventListener('message', function (event) {
            const messagesDiv = document.getElementById('messages');
            messagesDiv.innerHTML += '<p>' + event.data + '</p>';
        });

        // Send message
        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value;

            // Send the message through the WebSocket
            socket.send(message);

            // Clear input field
            messageInput.value = '';
        }
    </script>
</body>
</html>
