<?php

class PollerModel extends Model
{
    static $tableName = 'Poller';
}

class Poller
{
    static $client;
    static $longPollSleep = 100; // in ms
    static $longPollKeepalive = 30; // in seconds
    static $backEnd = 'fmt';

    static function create($options = array()){

        foreach ($options as $key => $value) {
            self::$key = $value;
        }

        App::xhrget('/sarahphp/poller/long', function(){
            $collections = Input::get();
            $now = time();
            $response = time() + self::$longPollKeepalive;
            $data = array();
            $message = 'NOOP';
            do {
                if(is_array($collections)) {
                    foreach ($collections as $collection => $mtime) {
                        $fmt = self::checkUpdated($collection);
                        if($fmt > $mtime) {
                            $message = "{$collection} Updated";
                            $data[$collection] = $fmt;
                            APP::XHR(200,$message, $data);
                            exit;
                        }
                    }
                }
                usleep(self::$longPollSleep * 1000);
            } while (time() < $response);
            APP::XHR(200,$message, $data);
        });

        App::xhrget('/sarahphp/poller/short', function(){
            $collections = Input::get();
            $data = array();
            $message = 'NOOP';
            if(is_array($collections)) {
                foreach ($collections as $collection => $mtime) {
                    $fmt = self::checkUpdated($collection);
                    if($fmt > $mtime) {
                        $message = "{$collection} Updated";
                        $data[$collection] = $fmt;
                        APP::XHR(200,$message, $data);
                        exit;
                    }
                }
            }
            APP::XHR(200,$message, $data);
        });

        App::xhrget('/sarahphp/poller/me', function() {
            if(User::isLoggedIn()) {
                $data = Session::get('_user');
                App::XHR(200,'OK',$data);
            } else {
                App::XHR(403,array());
            }
        });
    }

    static function setUpdated($collectionName) {
        if(self::$backEnd === 'fmt') {
            @touch('../resource/poll/' . $collectionName);
        } else {
            try {
                if(!class_exists('Predis\Client')) {
                    throw new Exception("Error Processing Request", 1);
                }
                if(self::$client) {
                    $redis = self::$client;
                } else {
                    $redis = self::$client = new Predis\Client();
                }
                $redis->set('MTIME:' . $collectionName, time());
                @touch('../resource/poll/' . $collectionName);
            } catch (Exception $e) {
                @touch('../resource/poll/' . $collectionName);
            }
        }
        clearstatcache();
    }

    static function checkUpdated($collectionName) {
        if(self::$backEnd === 'fmt') {
            if(!file_exists('../resource/poll/' . $collectionName))
                @touch('../resource/poll/' . $collectionName);
            clearstatcache();
            $fmt = filemtime('../resource/poll/' . $collectionName);
        } else {
            try {
                if(self::$client) {
                    $redis = self::$client;
                } else {
                    $redis = self::$client = new Predis\Client();
                }
                $fmt = $redis->get('MTIME:' . $collectionName);
                if($fmt === NULL || $fmt === '0') {
                    if(!file_exists('../resource/poll/' . $collectionName)) {
                        self::setUpdated($collectionName);
                    }
                    $fmt = filemtime('../resource/poll/' . $collectionName);
                    clearstatcache();
                    $redis->set('MTIME:' . $collectionName, $fmt);
                }
            } catch (Exception $e) {
                Logger::log('Redis Failed : GET');
                $fmt = filemtime('../resource/poll/' . $collectionName);
                clearstatcache();
            }
        }
        return $fmt;
    }

    private static function _getModel($collectionName) {
        $model = PollerModel::all()->filterOne('name', $collectionName);
        if($model === null) {
            $model = PollerModel::create();
            $model->name = $collectionName;
            $model->lastRequest = '';
            $model->save();
        }
        return $model;
    }

    static function add($collectionName)
    {

        App::xhrget('/sarahphp/poller/' . $collectionName . '/:any', function() use ($collectionName){
            $model = self::_getModel($collectionName)->fetch();
            $data = array('collection' => $collectionName::filter('*', function($item){
                $params = Input::params();
                return strtotime($item['updatedAt']) > $params[0] || strtotime($item['deletedAt']) > $params[0] || strtotime($item['createdAt']) > $params[0];
            })->fetch('array'), 'rid' => $model['lastRequest']);

            App::XHR(200,'OK',$data);
        });

        App::xhrpost('/sarahphp/poller/' . $collectionName, function() use ($collectionName){
            $postData = Input::post();
            $data = $collectionName::create($postData['data']);
            $data->save();
            $data = $data->fetch();

            /* Set last request ID */
            $requestId = $postData['rid'];
            $model = self::_getModel($collectionName);
            $model->lastRequest = $requestId;
            $model->save();

            // model already touched poller
            self::setUpdated($collectionName);
            App::XHR(201 ,$data);
        });

        App::xhrput('/sarahphp/poller/' . $collectionName .'/:any', function() use ($collectionName){
            $putData = Input::put();
            $data = $collectionName::create($putData['data']);
            $data->save();

            /* Set last request ID */
            $requestId = $putData['rid'];
            $model = self::_getModel($collectionName);
            $model->lastRequest = $requestId;
            $model->save();

            // model already touched poller
            self::setUpdated($collectionName);
            App::XHR(200,$data);
        });

        App::xhrdelete('/sarahphp/poller/' . $collectionName .'/:any', function() use ($collectionName){
            $params = Input::params();
            $deleteData = Input::delete();
            $data = $collectionName::find($params[0]);
            $data->deletedAt = date('c');
            $data->save();

            /* Set last request ID */
            $requestId = $deleteData['rid'];
            $model = self::_getModel($collectionName);
            $model->lastRequest = $requestId;
            $model->save();

            // model already touched poller
            self::setUpdated($collectionName);
            App::XHR(200,array('id' => $data->fetch()));
        });
    }
}

Poller::create();
