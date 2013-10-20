<?php

require_once('../system/Profiler.php');

date_default_timezone_set('Asia/Kuala_Lumpur');

/* Autoload all vendors */
require_once('../vendor/autoload.php');

/*  Register Autoloader */
spl_autoload_register(function($class) {
    $folder = 'system';
    $folder2 = 'libraries';
    $file =  $class . '.php';
    $fileName = '..' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $class . '.php';
    $fileName2 = '..' . DIRECTORY_SEPARATOR . $folder2 . DIRECTORY_SEPARATOR . $class . '.php';
    if(file_exists($fileName)) {
        require_once $fileName;
        Profiler::fileLoaded($folder . DIRECTORY_SEPARATOR . $file);
    } else if (file_exists($fileName2)) {
        require_once $fileName2;
        Profiler::fileLoaded($folder2 . DIRECTORY_SEPARATOR . $file);
    }
});

/* Define Base URL */

define('BASEURL', Uri::baseUrl());

/* Load Helper Functions */
Filesystem::loadFile('SYS/Helpers');

/* Load all in Application Folder */
Filesystem::LoadFolder('APP');

/* Go! Go! Go!!! */
App::go();
