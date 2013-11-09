<?php

class App
{
    static $paths;

    private static $_currentPath = null;

    public static function __callStatic($name, $arguments)
    {
        $name = strtolower($name);
        if(in_array($name, array(
            'get',
            'post',
            'webget',
            'webpost',
            'xhrget',
            'xhrpost',
            'xhrput',
            'xhrpatch',
            'xhrdelete',
            'all',
            'any'
        ))) {
            $validCurrentMethods = array('all', 'any');
            $request_method = $validCurrentMethods[] = strtolower($_SERVER['REQUEST_METHOD']);

            /* IS XHR */
            if(self::isXHR()) {
                $validCurrentMethods[] = 'xhr' . $request_method;
            } else {
                $validCurrentMethods[] = 'web' . $request_method;
            }

            if(in_array($name, $validCurrentMethods)) {

                $argPath = array();

                if($name === 'any') {
                    $targ = array();
                    $targ = array_map(function($arg) use ($validCurrentMethods){
                        if(!is_callable($arg)) {
                            foreach ($arg as $key => $value) {
                                if(in_array($key, $validCurrentMethods)) {
                                    return $value;
                                }
                            }
                        }
                    }, $arguments);

                    $targ = array_filter($targ, function($value){
                        return $value !== null;
                    });

                    foreach ($targ as $tar) {
                        if(substr($tar, -1) !== '/')
                            $tar = $tar . '/';
                        $argPath[$tar] = $arguments[1];
                    }

                } else {
                    if(substr($arguments[0], -1) !== '/')
                        $arguments[0] = $arguments[0] . '/';
                    $argPath[$arguments[0]] = $arguments[1];
                }

                if(!is_array(self::$paths)) {
                    self::$paths = array();
                }

                self::$paths = array_merge(self::$paths, $argPath);
            }
        } else {
            Throw new Exception("APP::{$name} method not found.");
            exit;
        }
    }

    static function Redirect($uri = '', $data = array()) {
        if ( ! preg_match('#^https?://#i', $uri))
        {
            $uri = url() . $uri;
        }
        if(count($data) > 0) {
            foreach ($data as $key => $value) {
                Session::setFlash($key, $value);
            }
        }
        header("Location: ".$uri, TRUE, 302);
        exit;
    }

    static function isXHR() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    static function Error($code = 500, $message = '', $data = array()) {
        if(self::isXHR()) {
            self::XHR($code, $message, $data);
        } else {
            switch ($code) {
                case 404:
                    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
                    header("Status: 404 Not Found");
                    $_SERVER['REDIRECT_STATUS'] = 404;
                     die("<center><h1>404</h1><hr><h3>{$message}</h3></center>");
                    break;
                default:
                    Throw new Exception('Unknown error');
                    break;
            }
        }
    }

    static function XHR($code, $clientMessage = null, $data = array()) {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');

        if(is_null($clientMessage)) {
            $data = array();
            $clientMessage = HTTPMessage($code);

        } else if(is_array($clientMessage)) {
            $data = $clientMessage;
            $clientMessage = HTTPMessage($code);
        }

        header($_SERVER["SERVER_PROTOCOL"]." {$code}");
        header("Status: {$code}");

        echo json_encode(array(
            'code'    => $code,
            'message' => $clientMessage,
            'data'    => $data
        ));
    }

    static function getCurrentPath()
    {
        return self::$_currentPath;
    }

    static function RouteExists($route)
    {
        if($route[0] !== '/')
            $route = '/' . $route;
        return self::go($route, false);
    }

    static function go($route = null, $callHandler = true) {
        $requestedPath = '/';
        if(is_null($route)) {
            if (!empty($_SERVER['PATH_INFO'])) {
                $requestedPath = $_SERVER['PATH_INFO'];
            }
            else if (!empty($_SERVER['ORIG_PATH_INFO']) && $_SERVER['ORIG_PATH_INFO'] !== '/index.php') {
                $requestedPath = $_SERVER['ORIG_PATH_INFO'];
            }
            else {
                if (!empty($_SERVER['REQUEST_URI'])) {
                    $requestedPath = (strpos($_SERVER['REQUEST_URI'], '?') > 0) ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'];
                }
            }
        } else {
            $requestedPath = $route;
        }

        if(substr($requestedPath, -1) !== '/')
            $requestedPath = $requestedPath . '/';


        $handler = null;
        $regex_matches = null;

        if (isset(self::$paths[$requestedPath])) {
            $handler = self::$paths[$requestedPath];
            self::$_currentPath = $requestedPath;
        }
        else if (self::$paths) {
            $tokens = array(
                ':alpha' => '([a-zA-Z]+)',
                ':number' => '([0-9]+)',
                ':alphanum'  => '([a-zA-Z0-9\-]+)',
                ':any'  => '(.*?)',
            );
            foreach (self::$paths as $path => $handler_name) {
                $pattern = strtr($path, $tokens);
                if (preg_match('#^/?' . $pattern . '/?$#', $requestedPath, $matches)) {
                    self::$_currentPath = $path;
                    $handler = $handler_name;
                    $regex_matches = $matches[1];
                    break;
                }
            }
        }

        if(is_callable($handler)){
            if($callHandler)
                echo $handler($regex_matches);
            else
                return true;
        } else {
            if($callHandler)
                self::error(404, 'Dude, where\'s my page?');
            else
                return false;
        }
    }
}

