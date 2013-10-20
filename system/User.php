<?php

class User
{
    static function get($item = null) {
        $userData = Session::get('_user');
        return ($item !== null && isset($userData[$item])) ? $userData[$item] : $userData;
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

    static function Login($username, $password) {
        $user = Users::filterOne('username', $username);
        if($user !== null && $user->password === md5($password)) {
            Session::Set('_user', $user->fetch());
            return $user;
        }
        return null;
    }

    static function Create($username, $password, $cpassword, $additionalData = array()) {
        $user = false;
        $checkDb = Users::filterOne('username', $username);
        if(is_null($checkDb) && $username !== '' && $password === $cpassword) {
            $user = Users::create();
            $user->username = $username;
            $user->password = md5($password);
            $user->groups = [];
            foreach ($additionalData as $key => $value) {
                $user->$key = $value;
            }
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