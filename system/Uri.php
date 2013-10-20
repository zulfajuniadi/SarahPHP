<?php

class Uri
{
    private static $segments;

    public static function baseUrl()
    {
        $proto = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $path = $proto . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '/';
        return isset($_SERVER['HTACCESS']) ? str_replace('index.php/', '', $path) : $path;
    }

    public static function all()
    {
        /* $_SERVER['PATH_INFO'] is unset if request has no paths (i.e. http://tests/tests/index.php). Hence: */

        if(!isset($_SERVER['PATH_INFO']))
            return array();

        self::$segments = explode('/', $_SERVER['PATH_INFO']);
        array_shift(self::$segments);
        return self::$segments;
    }

    public static function segment($segment = 0)
    {
        if(!isset(self::$segments)) {
            self::all();
        }
        return isset(self::$segments[$segment]) ? self::$segments[$segment] : null;
    }
}