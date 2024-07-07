<?php
//used on initialization of playerEngine to get root dir of project
function getAppDir() {
    return __DIR__;
}

//to get root dir of user data. Songs & patches. Possibly skins.
function getUserDir() {
    return __DIR__ . '/user';
}