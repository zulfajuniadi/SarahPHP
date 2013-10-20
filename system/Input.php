<?php

class Input
{

    private static function _rehydrate($data) {
        if(is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::_rehydrate($value);
            }
            return $data;
        } else {
            if($data === '0') {
                $return = 0;
            } else if (is_numeric($data)) {
                $return = floatval($data);
            } else if ($data === '') {
                $return = null;
            } else if ($data === 'true') {
                $return = true;
            } else if ($data === 'false') {
                $return = false;
            } else if(strlen($data) > 6 && strtotime($data) !== false) {
                $return = date('c', strtotime($data));
            }
            else {
                $return = $data;
            }
            return $return;
        }
    }

    static function get($item = null) {
        $data = self::_rehydrate($_GET);
        if($item !== null) {
            if(isset($data[$item])) {
                return $data[$item];
            }
            return null;
        }
        return $data;
    }

    static function post($item = null) {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        if(json_last_error() !== JSON_ERROR_NONE || $input === '') {
            $data = $_POST;
        }
        if($data)
            $data = self::_rehydrate($data);
        if($item !== null) {
            if(isset($data[$item])) {
                return $data[$item];
            }
            return null;
        }
        return $data;
    }

    static function files() {
        return $_FILES;
    }

    static function put($item = null) {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        if(json_last_error() !== JSON_ERROR_NONE) {
            parse_str($input,$data);
        }
        $data = self::_rehydrate($data);
        if($item !== null) {
            if(isset($data[$item])) {
                return $data[$item];
            }
            return null;
        }
        return $data;
    }

    static function delete($item = null) {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        if(json_last_error() !== JSON_ERROR_NONE) {
            parse_str($input,$data);
        }
        $data = self::_rehydrate($data);
        if($item !== null) {
            if(isset($data[$item])) {
                return $data[$item];
            }
            return null;
        }
        return $data;
    }

    static function params($segment = null) {
        $requestedPath = '/';
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

        if(substr($requestedPath, -1) !== '/')
            $requestedPath = $requestedPath . '/';

        $tokens = array(
            ':string' => '([a-zA-Z]+)',
            ':number' => '([0-9]+)',
            ':alphanum'  => '([a-zA-Z0-9-]+)',
            ':any'  => '(.*?)',
        );

        $pattern = strtr(App::getCurrentPath(), $tokens);
        $regex_matches = array();
        if (preg_match('#^/?' . $pattern . '/?$#', $requestedPath, $matches)) {
            $regex_matches = $matches;
        }

        if(isset($regex_matches)) {
            $ret = array();
            foreach ($regex_matches as $index => $value) {
                if($index > 0) {
                    $ret[] = $value;
                }
            }
            return $ret;
        }
        return null;
    }

}