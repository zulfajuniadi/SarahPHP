<?php

require_once('../system/Profiler.php');

date_default_timezone_set('Asia/Kuala_Lumpur');

/* Autoload all vendors */
require_once('../vendor/autoload.php');

/*  Register Autoloader */
spl_autoload_register(function($class) {
    $systemFolder = 'system';
    $librariesFolder = 'libraries';
    $file =  $class . '.php';
    $systemFile = '..' . DIRECTORY_SEPARATOR . $systemFolder . DIRECTORY_SEPARATOR . $class . '.php';
    $libraryFile = '..' . DIRECTORY_SEPARATOR . $librariesFolder . DIRECTORY_SEPARATOR . $class . '.php';
    if(file_exists($systemFile)) {
        require_once $systemFile;
        Profiler::fileLoaded($systemFolder . DIRECTORY_SEPARATOR . $file);
    } else if (file_exists($libraryFile)) {
        require_once $libraryFile;
        Profiler::fileLoaded($librariesFolder . DIRECTORY_SEPARATOR . $file);
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
