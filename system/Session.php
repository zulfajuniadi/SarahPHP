<?php

session_start();
session_write_close();
class Session
{
    static function get($key = null, $default = null) {
        if(isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return $default;
    }

    static function set($key, $value) {
        session_start();
        $_SESSION[$key] = $value;
        session_write_close();
    }

    static function setFlash($key, $value) {
        session_start();
        if(!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = array();
        }
        $_SESSION['_flash'][$key] = $value;
        session_write_close();
    }

    static function getFlash($key, $default = null) {
        session_start();
        if(!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = array();
        }
        if(isset($_SESSION['_flash'][$key])) {
            $data = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            session_write_close();
            return $data;
        }
        session_write_close();
        return null;
    }

    static function destroy() {
        session_start();
        session_destroy();
        session_write_close();
    }
}