<?php

class Profiler
{
    static private $_enabled = false;
    static private $_timestamp;
    static private $_messages;
    static private $_timestamps;
    static private $_loaded;

    static private function _getTrace()
    {
        $bt = debug_backtrace();
        $directorySegments = explode(DIRECTORY_SEPARATOR, $bt[1]['file']);
        $fileName = array_pop($directorySegments);
        $fileDir = array_pop($directorySegments);
        return '[' . $fileDir . DIRECTORY_SEPARATOR . $fileName . ':' . $bt[1]['line'] . ']';
    }

    static function Enable()
    {
        self::$_enabled = true;
        self::$_messages['info'][] = [
            'Timestamp' => round((microtime() - self::$_timestamp) * 1000, 3) . ' ms',
            'Message' => '"Profiler Enabled"',
            'Meta' => self::_getTrace()
        ];
    }

    static function IsEnabled()
    {
        return self::$_enabled;
    }

    static function Disable()
    {
        self::$_enabled = false;
        self::$_messages = null;
        self::$_timestamp = null;
        self::$_timestamps = null;
        self::$_loaded = null;
    }

    static function FileLoaded($name)
    {
        $nameParts = explode(DIRECTORY_SEPARATOR, $name);
        $file = array_pop($nameParts);
        $folder = array_pop($nameParts);
        self::$_loaded[] = $folder . DIRECTORY_SEPARATOR . $file;
    }

    static function Info($message)
    {
        if(is_object($message) || is_array($message)) {
            $message = '[' . json_encode($message) . ']';
        } else {
            $message = '"' . $message . '"';
        }
        $ts = microtime();
        self::$_messages['info'][] = [
            'Timestamp' => round(($ts - self::$_timestamp) * 1000, 3) . ' ms',
            'Message' => $message,
            'Meta' => self::_getTrace()
        ];
    }

    static function Debug($message)
    {
        if(is_object($message) || is_array($message)) {
            $message = '[' . json_encode($message) . ']';
        } else {
            $message = '"' . $message . '"';
        }
        $ts = microtime();
        $deltaT = $ts - self::$_timestamps['info'][0];
        array_unshift(self::$_timestamps['info'],$ts);
        self::$_messages['debug'][] = [
            'Timestamp' => round(($ts - self::$_timestamp) * 1000, 3) . ' ms',
            'Message' => $message,
            'Meta' => self::_getTrace()
        ];
    }

    static function Bench($message)
    {
        if(is_object($message) || is_array($message)) {
            $message = '[' . json_encode($message) . ']';
        } else {
            $message = '"' . $message . '"';
        }
        $ts = microtime();
        self::$_messages['bench'][] = [
            'Timestamp' => round(($ts - self::$_timestamp) * 1000, 3) . ' ms',
            'Message' => $message,
            'Meta' => self::_getTrace()
        ];
    }

    static function Memory()
    {
        $ts = microtime();
        self::$_messages['memory'][] = [
            'Timestamp' => round(($ts - self::$_timestamp) * 1000, 3) . ' ms',
            'Message' => '"'. round((memory_get_usage() / 1024 / 1024), 2) . ' MB' .'"',
            'Meta' => self::_getTrace()
        ];

    }

    static function Render($asString = false)
    {
        if(!self::$_enabled)
            return;

        $str = '<script>';
        $str .= 'console.log("Application Profile");' . "\n";
        $str .= 'console.log("======================================");' . "\n";
        $str .= 'console.log("Runtime: ' . round((microtime() - self::$_timestamp) * 1000, 3) . ' ms");' . "\n";
        $str .= 'console.log("Peak Mem: ' . round(memory_get_peak_usage() /1024/1024, 3) . ' MB");' . "\n";
        $str .= 'console.log("Loaded Files:");'. "\n";
        foreach (self::$_loaded as $loaded) {
            $str .= 'console.log(" - ' . $loaded . '");'. "\n";
        }
        $str .= 'console.log("");' . "\n";
        if(count(self::$_messages['info']) > 0) {
            $str .= 'console.log("Information");' . "\n";
            $str .= 'console.log("======================================");' . "\n";
            foreach (self::$_messages['info'] as $entry) {
                $str .= 'console.log("'.$entry['Timestamp'].'", ' . $entry['Message'] . ',"'.$entry['Meta'].'");' . "\n";
            }
            $str .= 'console.log("");' . "\n";
        }
        if(count(self::$_messages['debug']) > 0) {
            $str .= 'console.log("Debugging");' . "\n";
            $str .= 'console.log("======================================");' . "\n";
            foreach (self::$_messages['debug'] as $entry) {
                $str .= 'console.log("'.$entry['Timestamp'].'", ' . $entry['Message'] . ',"'.$entry['Meta'].'");' . "\n";
            }
            $str .= 'console.log("");' . "\n";
        }
        if(count(self::$_messages['benchmark']) > 0) {
            $str .= 'console.log("Benchmarks");' . "\n";
            $str .= 'console.log("======================================");' . "\n";
            foreach (self::$_messages['benchmark'] as $entry) {
                $str .= 'console.log("'.$entry['Timestamp'].'", ' . $entry['Message'] . ',"'.$entry['Meta'].'");' . "\n";
            }
            $str .= 'console.log("");' . "\n";
        }
        if(count(self::$_messages['memory']) > 0) {
            $str .= 'console.log("Memory");' . "\n";
            $str .= 'console.log("======================================");' . "\n";
            foreach (self::$_messages['memory'] as $entry) {
                $str .= 'console.log("'.$entry['Timestamp'].'", ' . $entry['Message'] . ',"'.$entry['Meta'].'");' . "\n";
            }
            $str .= 'console.log("");' . "\n";
        }
        $str .= 'console.log("======================================");' . "\n";
        $str .= 'console.log("");' . "\n";
        $str .= 'console.log("  ");' . "\n";
        $str .= 'console.log("   ");' . "\n";
        $str .= '</script>' . "\n";

        if(!App::isXHR() && !$asString) {
            echo $str;
            return;
        }
        return $str;
    }

    static function initArrays()
    {
        self::$_timestamp = microtime();
        self::$_timestamps = [];
        self::$_loaded = [];
        self::$_messages = [];
        self::$_messages['info'] = [];
        self::$_messages['debug'] = [];
        self::$_messages['benchmark'] = [];
        self::$_messages['memory'] = [];
        self::$_timestamps['info'] = [self::$_timestamp];
        self::$_timestamps['debug'] = [self::$_timestamp];
        self::$_timestamps['benchmark'] = [self::$_timestamp];
        self::$_timestamps['memory'] = [self::$_timestamp];
    }
}

Profiler::initArrays();

register_shutdown_function(function(){
    if(Profiler::isEnabled()) {
        Profiler::render();
    }
});