<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Event Tracing</title>
</head>
<body>
<div id="target">Hover over me to trigger events</div>

<script>
// Get the target element
var target = document.getElementById('target');

// Function to log all fired events
function logEvent(event) {
    console.log('Event fired:', event.type);
}

// Attach event listeners for all possible events
var events = [
    'click', 'dblclick', 'mousedown', 'mouseup', 'mouseenter', 'mouseleave', 'mousemove', 'mouseover', 'mouseout',
    'keydown', 'keypress', 'keyup',
    'focus', 'blur', 'change', 'input', 'submit',
    'touchstart', 'touchend', 'touchmove', 'touchcancel',
    'drag', 'dragstart', 'dragend', 'dragenter', 'dragleave', 'dragover', 'drop',
    'scroll', 'resize', 'load', 'unload', 'error'
];

events.forEach(function(eventName) {
    target.addEventListener(eventName, logEvent);
});
</script>
</body>
</html>
