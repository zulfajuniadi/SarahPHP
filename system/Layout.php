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

        $layoutPartials = [];
            
        foreach ($partials as $path) {
            $paths = array_merge(self::$layoutsDir, ['views'],  explode('.', $path));
            $viewPath = join(DIRECTORY_SEPARATOR, $paths) . '.php';

            if(!file_exists($viewPath))
                throw new Exception("File {$viewPath} does not exists." , 1);

            $layoutPartials[] = $viewPath;
        }

        self::$layouts[$layoutPath] = $layoutPartials;

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