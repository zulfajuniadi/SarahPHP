<?php

class Layout
{
    private static $layouts = [];
    private static $layoutsDir = ['..','resource', 'layouts'];

    static function create($name, $partials = array())
    {
        $paths = array_merge(self::$layoutsDir, explode('.', $name));

        $layoutPath = join(DIRECTORY_SEPARATOR, $paths) . '.php';

        if(!file_exists($layoutPath))
            throw new Exception("File {$layoutPath} does not exists." , 1);
            


        return print_r($paths, true);
    }

    static function assign($name, $vars = array())
    {

    }

    static function setView($name, $viewpath)
    {

    }

    static function render($name, $data)
    {

    }
}