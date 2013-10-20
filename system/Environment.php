<?php

class Environment
{
    private static $environment;

    private static $environments;

    static function add($environment, $detector, $callback = null)
    {
        $isCurrentEnv = false;

        if(!is_callable($detector)) {
            throw new Exception('Second argument for Environment::add must be a callable function.');
        }

        if($detector()) {
            self::$environment = $environment;
            $isCurrentEnv = true;
        }

        if(!is_array(self::$environments))
            self::$environments = array();

        self::$environments[] = $environment;

        if(is_callable($callback) && $isCurrentEnv){
            return $callback();
        } else {
            return true;
        }
    }

    static function is($environment, $callback = null)
    {
        if(!isset(self::$environment)) {
            throw new Exception('Setup environment using Environment::add(string $env, function $detector) first!');
        }

        $ret = self::$environment === $environment;

        if(is_callable($callback) && $ret) {
            return $callback();
        }

        return self::$environment === $environment;
    }

    static function getAll()
    {
        return self::$environments;
    }
}