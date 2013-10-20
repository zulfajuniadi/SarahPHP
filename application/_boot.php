<?php

/* Configure Default Assets */

Environment::add('development', function(){
    return stristr(BASEURL, 'sarahphp.dev');
}, function(){
    Model::setConnection('sqlite:../resource/database/dev.db');
    error_reporting(E_ALL);
    if(!App::isXHR()) {
        Profiler::Enable();
    }
});

Environment::add('production', function(){
    return stristr(BASEURL, 'sarahphp.zulfajuniadi.com');
}, function(){
    Model::setConnection('sqlite:../resource/database/prod.db');
});

if(App::isXHR()) {
    error_reporting(0);
}

/* Define ur models */

// class Projects extends Model{
//     static $tableName = 'Projects';
// };

// Poller::add('Projects');

// Poller::add('Users');

Assets::configure(array(
    "baseUrl" => BASEURL,
    "assetDirectory" => "",
    "cacheDirectory" => "../resource/cache"
));

/* Configure Default Assets */

// Assets::enqueue(
//     'source/css/jquery-ui.css',
//     'source/css/bootstrap.css',
//     'source/css/font-awesome.min.css',
//     'source/js/jquery.js',
//     'source/js/bootstrap.js',
//     'source/js/moment.js',
//     'source/css/style.css'
// );

