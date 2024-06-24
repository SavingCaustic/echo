<?php

//if FE marks off-events too, no need to ref.
//and ref problematic, as they are not recorded.

class PatternPlayer {
    var $events;

    function test() {
        //event:
        // tick,id,cmd,note,vel 
        //id is 96*90*60. Chill
        // [] = [96,90,60,120]
        // [] = [96,90,32,50]
        // [] = [200,80,32,0]
        // [] = [300,80,60,0]
    }
    //when to re-order on note move?
}