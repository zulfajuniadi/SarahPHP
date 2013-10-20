<?php


class Logger
{
    static $keep = 7;
    static $path = '../resource/logs/';

    static function Log() {

        $filename = self::$path . date('Y-m-d') . '.log';

        touch($filename);

        $args = func_get_args();
        if(count($args) === 1) {
            if(!is_array($args[0]) && !is_object($args[0])) {
                $str = $args[0];
            }
        }
        if(!isset($str))
            $str = var_export(func_get_args(), true);

        file_put_contents($filename, date('Y-m-d H:i:s') . "\t" . $str . "\r\n", FILE_APPEND);
    }
}