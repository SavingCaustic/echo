<?php
        if (!function_exists('imagepng')) {
            die('you *really* need imagegd extension for this to work..');
        }
?>
<html>
    <body style="margin:0px 0px;" bgcolor="#000000">
        <div style="display:flex;flex-direction:column;height:100vh;background-color:#f00;">
            <div style="height:calc(100% - 5rem);">
                <iframe src="percent.html" width="100%" height="100%"></iframe>
            </div>
            <div style="height:5rem;background-color:#555;">
                stuff
            </div>            
        </div>
    </body>
</html>
