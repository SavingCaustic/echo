<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Stream Player</title>
</head>
<body>
    <h1>Audio Stream Player</h1>
    <p>Reminder: Firefox can't play streamed wav-files. Revert to Chrome (which also refuses to play..).</p>
    <audio controls autoplay>
        <source src="wavServer.php" type="audio/wav">
        Your browser does not support the audio element.
    </audio>
</body>
</html>
