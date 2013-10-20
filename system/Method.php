<?php

class Method
{

    static private $methods;

    static function create($name, $fn)
    {
        if(!is_array(self::$methods)) {
            throw new Exception("Run Method::init() first!.");
        }

        if(in_array($name, self::$methods)) {
            throw new Exception("Method {$name} already declared");
        }

        self::$methods[] = $name;

        App::XHRGET('/methods/run/' . $name, function() use ($fn) {
            $vars = Input::get();
            try {
                $data = $fn($vars);
                APP::XHR(200, 'OK', $data);
            } catch (Exception $e) {
                APP::XHR(500, 'ERROR EXECUTING METHOD', array());
            }
        });
    }

    static function init()
    {
        if(!self::$methods){
            self::$methods = array();
        }

        App::XHRGET('/methods/list', function() {
            try {
                APP::XHR(200, 'OK', self::$methods);
            } catch (Exception $e) {
                APP::XHR(500, 'ERROR EXECUTING METHOD', array());
            }
        });
    }

    static function getAll()
    {

    }
}

Method::init();