<?php

class User
{
    static function get($item = null) {
        $userData = Session::get('_user');
        return (isset($userData[$item])) ? $userData[$item] : '';
    }

    static function belongsTo($name, $user = 'me') {

    }

    static function assignGroup($gid, $uid = 'me') {

    }

    static function isLoggedIn() {
        if(!is_null(Session::get('_user'))) {
            return true;
        }
        return false;
    }

    static function setUser($uid) {
        $user = Users::find($uid)->fetch('array');
        if($user !== null) {
            Session::Set('_user', $user);
        }
    }

    static function Login($username, $password, $success = null, $error = null) {
        $user = Users::filterOne('username', $username);
        if($user !== null && $user->password === md5($password)) {
            Session::Set('_user', $user->fetch());
            if(is_callable($success)) {
                return $success($user);
            }
            return $user;
        } else {
            if(is_callable($error)) {
                return $error();
            }
            return false;
        }
    }

    static function Create($username, $password, $cpassword) {
        $user = false;
        $checkDb = Users::filterOne('username', $username);
        if(is_null($checkDb) && $username !== '' && $password === $cpassword) {
            $user = Users::create();
            $user->username = $username;
            $user->password = md5($password);
            $user->groups = [];
            $user->save();
            return $user->fetch();
        }
        return null;
    }

    static function Logout(){
        /* this is correct, don't ask why. I also dunno. */
        Session::set('_user', null);
        Session::destroy();
    }
}