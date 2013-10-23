<?php

/* Setup Default Timezone */

date_default_timezone_set('Asia/Kuala_Lumpur');

/* Set Environment Variables */

$env = [
    'development' => 'sarahphp.dev',
    'staging'     => 'sarahphp.stg',
    'production'  => 'sarahphp.com'
];

foreach ($env as $name => $hostname) {
    Environment::add($name, function() use ($hostname) {
        return stristr(BASEURL, $hostname) !== false; 
    });
}

/* Setup the application based on the detected environment */

Environment::is('development', function(){
    
    Model::setConnection('sqlite:../resource/database/development.db');
    error_reporting(E_ALL);

});

Environment::is('staging', function(){

    Model::setConnection('sqlite:../resource/database/development.db');
    error_reporting(E_ERROR);

});

Environment::is('production', function(){

    Model::setConnection('sqlite:../resource/database/production.db');
    error_reporting(0);

});


/* Setup the application Models */

/*
    class Tasks extends Model
    {
        static $tableName = 'tasks';
    }
*/


/* Uncomment to use SarahPHP Poller */

/*
    Poller::create();
*/


/* Uncomment to add a model to SarahPHP Poller */

/*
    Poller::add('Tasks');
*/