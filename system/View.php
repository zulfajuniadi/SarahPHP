<?php

class View
{
    static $data;
    static function render($templateFilePath, $data = array(), $toString = true) {
        if(is_array(self::$data)){
            extract(self::$data);
        }
        extract($data);
        if($toString) {
            ob_start();
            include('../resource/views/' . $templateFilePath . '.php');
            return ob_get_clean();
        } else {
            include('../resource/views/' . $templateFilePath . '.php');
        }
    }

    static function assign(array $data)
    {
        if(!is_array($self::$data)) {
            self::$data = array();
        }
        self::$data = array_merge(self::$data, $data);
    }

    static function layout($layoutFile)
    {

    }
}