<?php

class Helpers
{
    static function uuid_v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
          mt_rand(0, 0xffff), mt_rand(0, 0xffff),
          mt_rand(0, 0xffff),
          mt_rand(0, 0x0fff) | 0x4000,
          mt_rand(0, 0x3fff) | 0x8000,
          mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}


if(!function_exists('HTTPMessage')) {
    function HTTPMessage( $code ) {
        $responses = array(
            '200' => 'OK',
            '201' => 'Created',
            '202' => 'Accepted',
            '204' => 'No Content',
            '205' => 'Reset Content',
            '206' => 'Partial Content',
            '302' => 'Found',
            '303' => 'See Other (since HTTP/1.1)',
            '304' => 'Not Modified',
            '305' => 'Use Proxy (since HTTP/1.1)',
            '306' => 'Switch Proxy',
            '307' => 'Temporary Redirect (since HTTP/1.1)',
            '308' => 'Permanent Redirect (approved as experimental RFC)[12]',
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '402' => 'Payment Required',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '406' => 'Not Acceptable',
            '407' => 'Proxy Authentication Required',
            '408' => 'Request Timeout',
            '409' => 'Conflict',
            '410' => 'Gone',
            '411' => 'Length Required',
            '412' => 'Precondition Failed',
            '413' => 'Request Entity Too Large',
            '414' => 'Request-URI Too Long',
            '415' => 'Unsupported Media Type',
            '416' => 'Requested Range Not Satisfiable',
            '417' => 'Expectation Failed',
            '419' => 'Authentication Timeout (not in RFC 2616)',
            '420' => 'Method Failure (Spring Framework)',
            '420' => 'Enhance Your Calm (Twitter)',
            '422' => 'Unprocessable Entity (WebDAV; RFC 4918)',
            '423' => 'Locked (WebDAV; RFC 4918)',
            '424' => 'Failed Dependency (WebDAV; RFC 4918)',
            '424' => 'Method Failure (WebDAV)[14]',
            '425' => 'Unordered Collection (Internet draft)',
            '426' => 'Upgrade Required (RFC 2817)',
            '428' => 'Precondition Required (RFC 6585)',
            '429' => 'Too Many Requests (RFC 6585)',
            '431' => 'Request Header Fields Too Large (RFC 6585)',
            '444' => 'No Response (Nginx)',
            '449' => 'Retry With (Microsoft)',
            '450' => 'Blocked by Windows Parental Controls (Microsoft)',
            '451' => 'Unavailable For Legal Reasons (Internet draft)',
            '494' => 'Request Header Too Large (Nginx)',
            '495' => 'Cert Error (Nginx)',
            '496' => 'No Cert (Nginx)',
            '497' => 'HTTP to HTTPS (Nginx)',
            '499' => 'Client Closed Request (Nginx)',
            '500' => 'Internal Server Error',
            '501' => 'Not Implemented',
            '502' => 'Bad Gateway',
            '503' => 'Service Unavailable',
            '504' => 'Gateway Timeout',
            '505' => 'HTTP Version Not Supported',
            '506' => 'Variant Also Negotiates (RFC 2295)',
            '507' => 'Insufficient Storage (WebDAV; RFC 4918)',
            '508' => 'Loop Detected (WebDAV; RFC 5842)',
            '509' => 'Bandwidth Limit Exceeded (Apache bw/limited extension)',
            '510' => 'Not Extended (RFC 2774)',
            '511' => 'Network Authentication Required (RFC 6585)',
            '599' => 'Network connect timeout error (Unknown)'
        );
        return $responses[$code];
    }
}

if(!function_exists('dd')) {
    function dd($item = null) {
        Logger::log($item);
        echo '<pre>';
        print_r($item);
        echo '</pre>';
        die(0);
    }
}


if(!function_exists('createClassName')) {
    function createClassName() {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $string = '';
        for ($i = 0; $i < 5; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
}

if(!function_exists('uuid_v4')) {
    function uuid_v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
          mt_rand(0, 0xffff), mt_rand(0, 0xffff),
          mt_rand(0, 0xffff),
          mt_rand(0, 0x0fff) | 0x4000,
          mt_rand(0, 0x3fff) | 0x8000,
          mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if(!function_exists('convertDate')) {
    function convertDate($item) {
        if(is_array($item)) {
            foreach ($item as $key => $value) {
                $item[$key] = convertDate($value);
            }
            return $item;
        } else if(is_string($item)) {
            $date = strtotime($item);
            if ($date) {
                return date('c', $date);
            }
        }
        return $item;
    }
}

if(!function_exists('validPropertyName')) {
    function validPropertyName($string) {
        $result = preg_match('/[;\*]/',$string);
        return ($result === 0) ? true : false;
    }
}

if(!function_exists('compareOperator')) {
    function compareOperator($v1, $v2, $operator) {
        switch ($operator) {
            case '===':
                return $v1 === $v2;
            case '<=':
                return $v1 <= $v2;
            case '>=':
                return $v1 >= $v2;
            case '<':
                return $v1 < $v2;
            case '>':
                return $v1 > $v2;
            case '!==':
                return $v1 !== $v2;
            case '==':
                return $v1 == $v2;
            case '!=':
                return $v1 != $v2;
        }
    }
}

if(!function_exists('url')) {
    function url($full = false) {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
        $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        if($full)
            return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
        else {
            return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;
        }
    }
}

