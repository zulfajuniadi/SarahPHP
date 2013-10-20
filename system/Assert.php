<?php

if(version_compare(phpversion(), '5.4', 'lt')) {
    throw new Exception("SarahPHP Requires PHP Version > 5.4. Current version is " . phpversion());
}

class Assert
{
    static private $fixtures;
    static private $ofixtures;
    static private $_successCount = 0;
    static private $_failureCount = 0;
    static private $_groupName = '';
    static private $messages;
    static private $_timestamp;
    static private $groupStatus;

    static private $_after;

    private static function _succ($message = 'Assertion Success')
    {
        if(!isset(self::$messages)){
            self::$messages = array();
        }
        if(!isset(self::$messages['success'])){
            self::$messages['all'] = array();
        }
        if(!isset(self::$messages['all'])){
            self::$messages['all'] = array();
        }
        $bt = debug_backtrace();
        self::$messages['success'][] = $message . '. in ' . $bt[1]['file'] . ' on line ' . $bt[1]['line'] . "\n";
        self::$messages['all'][self::$_groupName][] = array(
            'result' => true,
            'status' => '<span class="success"></span>',
            'message' => $message,
            'file' => $bt[1]['file'],
            'line' => $bt[1]['line']
        );

        self::$_successCount++;
        return true;
    }

    private static function _fail($message = 'Assertion failure')
    {
        if(!isset(self::$messages)){
            self::$messages = array();
        }
        if(!isset(self::$messages['fail'])){
            self::$messages['fail'] = array();
        }
        if(!isset(self::$messages['all'])){
            self::$messages['all'] = array();
        }
        $bt = debug_backtrace();
        self::$messages['fail'][] = $message . '. In ' . $bt[1]['file'] . ' on line ' . $bt[1]['line'] . "\n";
        self::$messages['all'][self::$_groupName][] = array(
            'result' => false,
            'status' => '<span class="fail"></span>',
            'message' => $message,
            'file' => $bt[1]['file'],
            'line' => $bt[1]['line']
        );
        self::$_failureCount++;
        return false;
    }

    static function is($value, $expected, $message = 'Assertion "IS" failure')
    {
        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if($value !== $expected)
                    return self::_fail($message . '. Expecting ' . $expected . ' got ' . print_r($value, true));
                else
                    return self::_succ($message);
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating $value for Assertion "IS". Expecting ' . $expected);
            }
        } else {
            if($value === $expected) {
                return self::_succ($message);
            } else {
                return self::_fail($message . '. Expecting ' . $expected . ' got ' . print_r($value, true));
            }
        }
    }

    static function isNot($value, $expected, $message = 'Assertion "ISNOT" failure')
    {
        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if($value === $expected)
                    return self::_fail($message . '. Not Expecting ' . $expected . ' got ' . print_r($value, true));
                else
                    return self::_succ($message);
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating $value for Assertion "ISNOT". Not Expecting ' . $expected);
            }
        } else {
            if($value === $expected) {
                return self::_fail($message . '. Not Expecting ' . $expected . ' got ' . print_r($value, true));
            } else {
                return self::_succ($message);
            }
        }
    }

    static function truthy($value, $message = 'Assertion "TRUTHY" Failure')
    {
        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if(!$value)
                    return self::_fail($message);
                else
                    return self::_succ($message);
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating function for Assert "TRUTHY"');
            }
        } else {
            if(!$value) {
                return self::_fail($message);
            } else {
                return self::_succ($message);
            }
        }
    }

    static function falsy($value, $message = 'Assertion "FALSY" Failure')
    {
        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if(!$value)
                    return self::_succ($message);
                else
                    return self::_fail($message);
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating function for Assert "FALSY"');
            }
        } else {
            if(!$value) {
                return self::_succ($message);
            } else {
                return self::_fail($message);
            }
        }
    }

    static function true($value, $message = 'Assertion "TRUTHY" Failure')
    {
        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if($value === true)
                    return self::_succ($message);
                else
                    return self::_fail($message);
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating function for Assert "TRUTHY"');
            }
        } else {
            if($value) {
                return self::_succ($message);
            } else {
                return self::_fail($message);
            }
        }
    }

    static function false($value, $message = 'Assertion "FALSY" Failure')
    {
        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if($value === false)
                    return self::_succ($message);
                else
                    return self::_fail($message);
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating function for Assert "FALSY"');
            }
        } else {
            if($value === false) {
                return self::_succ($message);
            } else {
                return self::_fail($message);
            }
        }
    }

    static function func($value, $func, $message = 'Assertion "FUNC" Failure') {
        if(!is_callable($func)) {
            throw new Exception('Func must be a callable function');
        }
        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if($func($value, self::$ofixtures)){
                    return self::_succ($message);
                } else {
                    return self::_fail($message);
                }
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating function for Assert "FUNC"');
            }
        } else {
            if($func($value, self::$ofixtures)) {
                return self::_succ($message);
            } else {
                return self::_fail($message);
            }
        }
    }

    static function before($callback)
    {
        if(is_callable($callback)) {
            $callback(self::$fixtures);
        }
    }

    static function after($callback)
    {
        if(!isset(self::$_after)){
            self::$_after = array();
        }
        if(is_callable($callback)) {
            self::$_after[] = $callback;
        }
    }

    static function type($value, $type = 'string', $message = 'Assertion "TYPE" Failure')
    {
        if(array_search($type, array(
            'boolean',
            'integer',
            'double',
            'string',
            'array',
            'resource',
            'object',
            'NULL'
        )) === false){
            self::_fail('Assert "TYPE" failure. type "' . $type . '"not found. Valid types are boolean, integer, double, array, resouce and NULL');
        }

        if(is_callable($value)) {
            try
            {
                $value = $value(self::$ofixtures);
                if($type === 'object' && is_object($value))
                    return self::_succ($message);
                else if(gettype($value) === $type)
                    return self::_succ($message);
                else
                    return self::_fail($message);
            }
            catch(Exception $e) {
                return self::_fail('Error Evaluating function for Assert "TYPE"');
            }
        } else {
            if($type === 'object' && is_object($value))
                return self::_succ($message);
            else if(gettype($value) === 'type')
                return self::_succ($message);
            else
                return self::_fail($message);
        }
    }

    static function setFixtures($name, $data = array())
    {
        self::$_timestamp = microtime();

        if(!isset(self::$ofixtures)) {
            self::$ofixtures = array();
        }

        if(is_callable($data)) {
            $data = $data();
        }

        if(!is_null($name)) {
            self::$ofixtures[$name] = $data;
        }
    }

    static function getFixtures($name)
    {
        if(isset(self::$ofixtures[$name])) {
            return self::$ofixtures[$name];
        }
        return null;
    }

    static function loadTests($name)
    {
        Filesystem::LoadFiles('TESTS/' . $name);
    }

    static function loadTest($name)
    {
        Filesystem::LoadFile('TESTS/' . $name);
    }

    static function results()
    {

        if(isset(self::$_after)) {
            foreach (self::$_after as $fn) {
                $fn();
            }
        }
        if(!isset(self::$messages)){
            self::$messages = array();
        }
        if(!isset(self::$messages['fail'])){
            self::$messages['fail'] = array();
        }
        if(!isset(self::$messages['success'])){
            self::$messages['success'] = array();
        }
        if(!isset(self::$messages['all'])){
            self::$messages['all'] = array();
        }
        echo '<style>* {font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; font-weight: 300;}table{width:100%;}table th{text-align:center;}.zebra * {vertical-align: top;}.zebra td, .zebra th {padding: 10px;border-bottom: 1px solid #f2f2f2;}.zebra tbody tr:nth-child(even) {background: #f5f5f5;-webkit-box-shadow: 0 1px 0 rgba(255,255,255,.8) inset;-moz-box-shadow:0 1px 0 rgba(255,255,255,.8) inset;box-shadow: 0 1px 0 rgba(255,255,255,.8) inset;}.zebra th {text-align: left;text-shadow: 0 1px 0 rgba(255,255,255,.5);border-bottom: 1px solid #ccc;background-color: #eee;background-image: -webkit-gradient(linear, left top, left bottom, from(#f5f5f5), to(#eee));background-image: -webkit-linear-gradient(top, #f5f5f5, #eee);background-image: -moz-linear-gradient(top, #f5f5f5, #eee);background-image: -ms-linear-gradient(top, #f5f5f5, #eee);background-image: -o-linear-gradient(top, #f5f5f5, #eee);background-image: linear-gradient(top, #f5f5f5, #eee);}.zebra th:first-child {-moz-border-radius: 6px 0 0 0;-webkit-border-radius: 6px 0 0 0;border-radius: 6px 0 0 0;}.zebra th:last-child {-moz-border-radius: 0 6px 0 0;-webkit-border-radius: 0 6px 0 0;border-radius: 0 6px 0 0;}.zebra th:only-child{-moz-border-radius: 6px 6px 0 0;-webkit-border-radius: 6px 6px 0 0;border-radius: 6px 6px 0 0;}.zebra tfoot td {border-bottom: 0;border-top: 1px solid #fff;background-color: #f1f1f1;}.zebra tfoot td:first-child {-moz-border-radius: 0 0 0 6px;-webkit-border-radius: 0 0 0 6px;border-radius: 0 0 0 6px;}.zebra tfoot td:last-child {-moz-border-radius: 0 0 6px 0;-webkit-border-radius: 0 0 6px 0;border-radius: 0 0 6px 0;}.zebra tfoot td:only-child{-moz-border-radius: 0 0 6px 6px;-webkit-border-radius: 0 0 6px 6px;border-radius: 0 0 6px 6px}body{padding-top:40px;}span.success {background-color:green; color:white; padding:2px 4px;}span.fail{background-color:red; color:white; padding:2px 4px;}span.total{background-color:darkblue; color:white; padding:2px 4px;}span.fail:after{content:" FAIL"}span.success:after{content:" SUCCESS"}span.total:after{content:" TOTAL"}.pull-right{float:right}.container {width:80%; margin:0 auto;}</style><div class="container">';
        echo '<h1>SarahPHP Unit Tests</h1>';
        echo '<p>Started: ' . date('c', intval(time() + self::$_timestamp)) . '</p>';
        echo '<p>Ended: ' . date('c') . '</p>';
        echo '<p>Runtime: ' . ((microtime() - self::$_timestamp) * 1000) . ' ms</p>';
        echo '<p>Assertions: ' . (self::$_successCount + self::$_failureCount) . '</p>';
        echo '<hr>';
        echo '<a name="summary"></a><h2>Test Summary</h2><table class="zebra" cellpadding="0" cellspacing="0"">
                <thead>
                    <tr>
                        <th> - </th>
                        <th> Status </th>
                        <th> Group </th>
                        <th> Details </th>
                    </tr>
                </thead><tbody>';
        $i = 1;
        $success = 0;
        $failed = 0;
        foreach (self::$messages['all'] as $groupName => $value) {
            if(self::$groupStatus[$groupName]) {
                $groupStatus = '<a name="'.$groupName.'"></a><span class="success"> </span>';
                $success ++;
            } else {
                $groupStatus = '<span class="fail"> </span>';
                $failed ++;
            }
            echo '<tr><td>'.$i.'</td><td>'.$groupStatus.'</td><td>'.$groupName.'</td><td> <a href="#group_'.$i.'">Details</a> </td></tr>';
            $i++;
        }
        echo '</tbody></table>';

        echo '<p><span class="success">'.$success.' </span></p>';
        echo '<p><span class="fail">'.$failed.' </span></p>';

        echo '<br>';
        echo '<hr>';
        $j = 1;
        foreach (self::$messages['all'] as $groupName => $data) {
            $k = 1;
            $groupStatus = (self::$groupStatus[$groupName]) ? '<span class="pull-right success"> </span>' : '<span class="pull-right fail"> </span>';

            echo '<a name="group_'.$j.'"></a><h2>'.$groupName.' '.$groupStatus.'</h2><hr/>
            <table class="zebra" cellpadding="0" cellspacing="0"">
                <thead>
                    <tr>
                        <th> - </th>
                        <th> Status </th>
                        <th> Message </th>
                        <th> File </th>
                        <th> Line </th>
                    </tr>
                </thead><tbody>';
                foreach ($data as $value) {
                    echo '<tr>
                        <td>'.$k.'</td>
                        <td>'.$value['status'].'</td>
                        <td>'.$value['message'].'</td>
                        <td>'.$value['file'].'</td>
                        <td>'.$value['line'].'</td>
                    </tr>';
                    $k++;

                }
            echo '</tbody></table><br><a class="pull-right" href="#summary">Back to Summary</a><br><br><br>';
            $j++;
        }

        echo '</div>';
    }

    static function _setGroup($name){
        self::$_groupName = $name;
    }

    static function group($name, $assertions, $callback = null)
    {
        if(!is_array(self::$groupStatus)) {
            self::$groupStatus = [];
        }
        self::$groupStatus[$name] = false;
        self::_setGroup($name);
        if(is_callable($assertions)) {
            $assertions();
            if(is_callable($callback)) {
                $results = self::$messages['all'][self::$_groupName];
                self::$groupStatus[$name] = $callback($results);
            }
        }
    }
}

Assert::setFixtures(null, array());