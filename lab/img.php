<?php
//can we reposition an image without using background-image?
//830px / 5 = 83*2=166
?>
<html>
    <body>
        <div style="position:relative;height:100px;overflow-x:hidden">
            <img src="img_LFO1_WF.png" style="position:absolute;overflow-x:hidden;margin-left:-332px;margin-right:-299px;">
        </div>
        <hr/>
        <div style="position:relative;">
            <div style="position:absolute;left:20px;top:30px;left:40px;border:1px solid #000;">
                <div style="width:166;height:50;background-image:url('img_LFO1_WF.png');"></div>
                </div>
            </div>
        </div>
    </body>
</html>