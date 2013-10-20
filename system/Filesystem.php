<?php

class Filesystem
{

    static $tokens;

    static function LoadFile($file)
    {
        $file = self::replaceTokens($file);
        if(!stristr($file,'.php'))
                $file = $file . '.php';
        if(is_file($file)) {
            Profiler::fileLoaded($file);
            require_once($file);
            return $file;
        } else {
            throw new Exception('File not found ' . $file);
        }
    }

    static function LoadFolder($folder, $filter = '*.php')
    {
        $folder = self::replaceTokens($folder);
        if(is_dir($folder)) {
            $globstr = $folder . '/' . $filter;
            return array_map(function($filename)
            {
                Profiler::fileLoaded($filename);
                require_once $filename;
                return $filename;
            }, glob($globstr));
        } else {
            throw new Exception('Folder does not exist ' . $folder);
        }
    }

    /* Alias for loadfolder */

    static function LoadFiles($folder, $filter = '*.php')
    {
        return self::LoadFolder($folder, $filter);
    }

    private static function replaceTokens($path)
    {
        $ret = str_replace(array_keys(self::$tokens), array_values(self::$tokens), $path);
        return $ret;
    }

    static function setDefaultFolders()
    {
        self::$tokens = array(
            'SYSTEM' => '../system',
            'SYS' => '../system',
            'VENDOR' => '../vendor',
            'APPLICATION' => '../application',
            'APP' => '../application',
            'TESTS' => '../tests',
            'RESOURCE' => '../resource',
            'RES' => '../resource',
            'PUBLIC' => '../public',
            'PUB' => '../public',
            'CACHE' => '../resource/cache',
            'DATABASE' => '../resource/database',
            'DB' => '../resource/database',
            'LOGS' => '../resource/logs',
            'POLL' => '../resource/poll',
            'SESSION' => '../resource/session',
            'VIEWS' => '../resource/views',
            'LIB' => '../libraries',
        );
    }
}

/* Shortcut */
class FS extends Filesystem {};

Filesystem::setDefaultFolders();