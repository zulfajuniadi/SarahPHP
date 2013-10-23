<?php

App::GET('/', function(){
    return 'Hello World!';
});

/* Targeting Get requests to the /render path originating from a browser */

App::WEBGET('/render', function(){
    return View::render('index', [
        'data' => 'Here!'
    ]);
});

/* Targeting Get requests to the /render path originating from an ajax call */

App::XHRGET('/render', function(){
    return json_encode(['outlet_here']);
});

/* Targeting requsts only on certain environments */

Environment::is('development', function(){
    App::GET('/wohoo', function(){
        return View::render('index', [
            'data' => 'Everywhere!'
        ]);
    });
});

Environment::is('staging', function(){
    App::GET('/noway', function(){
        return View::render('index', [
            'data' => 'but not here :('
        ]);
    });
});

