<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rotating Potentiometer Cap</title>
<style>
    /* Style for the container div */
    #container {
        width: 300px;
        height: 300px;
        border: 1px solid #ccc;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden; /* Ensure the rotating cap stays within the container */
        position: relative; /* Required for absolute positioning */
    }

    #capbg {
        width: 100px;
        height: 100px;
        border:2px solid #fa0;
        position: absolute;
        top: 50%;
        left: 25%;
        transform-origin: center center; /* Rotate around the center */

    }
    /* Style for the rotating cap */
    #cap {
        width: 100px;
        height: 100px;
        background-image: url('cap.png'); /* Replace with your image */
        background-size: cover;
        position: absolute;
        top: 50%;
        left: 25%;
        transform-origin: center center; /* Rotate around the center */
    }
</style>
</head>
<body>
<div id="container">
    <div id="capbg"></div>
    <div id="cap"></div>
    <div id="debug"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
<script>
// Get the rotating cap element
var cap = document.getElementById('cap');
var debug = document.getElementById('debug');

// Flag to track if dragging is in progress
var isDragging = false;
// Initial rotation angle when dragging starts
var initialAngle = 0;
//
var dragOrientation = 'V';

// Create a Hammer.js instance for the rotating cap
var hammertime = new Hammer(cap, {
    // Adjust sensitivity and threshold for detecting gestures
    threshold: 5, // Not working.. Set the threshold to 0 for immediate detection
    velocity: 0.1 // Adjust sensitivity as needed
});
hammertime.get('pan').set({ enable: true, direction: Hammer.DIRECTION_VERTICAL, threshold: 5 });


// Add a panstart event handler to track drag start
hammertime.on('panstart', function(event) {
    isDragging = true;
    // Store the initial rotation angle when dragging starts
    initialAngle = parseFloat(cap.style.transform.replace('rotate(', '').replace('deg)', '') || 0);
    if (Math.abs(event.deltaY) > Math.abs(event.deltaX)) {
        dragOrientation = 'V';
    } else {
        dragOrientation = 'H';
    }
});

// Add a panend event handler to track drag end
hammertime.on('panend', function(event) {
    isDragging = false;
});

hammertime.on('pan', function(event) {
        if (isDragging) {
            //almost there. we need to keep track of last pos, not original pos.
            //or it's either or when we start.
            if (dragOrientation == 'V') {
                var angle = initialAngle - event.deltaY * 0.6; // Adjust sensitivity as needed
                debug.innerHTML = 'Y:' + event.deltaY;
                // Ensure the rotation angle stays within 270 degrees
                angle = Math.min(Math.max(angle, -135), 135);
                // Apply rotation to the cap
                cap.style.transform = 'rotate(' + angle + 'deg)';
            } else {
                var angle = initialAngle + event.deltaX * 0.2; // Adjust sensitivity as needed
                debug.innerHTML = 'X:' + event.deltaX;
                // Ensure the rotation angle stays within 270 degrees
                angle = Math.min(Math.max(angle, -135), 135);
                // Apply rotation to the cap
                cap.style.transform = 'rotate(' + angle + 'deg)';
            }
        }
});


</script>
</body>
</html>
