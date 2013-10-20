<?php
class Model
{
    private static $db = null;
    private $name = null;
    private $keyColumnName = 'key';
    private $valueColumnName = 'value';

    public static $tableName;
    public static $collectionName;
    public $data = array();

    protected static $Instance;

    private function createTable()
    {
        $db = self::$db;
        $stmt = 'CREATE TABLE IF NOT EXISTS "' . $this->name;
        $stmt.= '" ("' . $this->keyColumnName . '" TEXT PRIMARY KEY, "';
        $stmt.= $this->valueColumnName . '" TEXT ,"';
        $stmt.= 'createdAt" TEXT, "';
        $stmt.= 'updatedAt" TEXT, "';
        $stmt.= 'deletedAt" TEXT);';
        $db->exec($stmt);
    }

    private static function _arrayFromResult($result)
    {
        $data = array();
        $data['_id'] = $result[0];
        $json = json_decode($result[1], true);
        $data = array_merge($data, $json);
        $data['createdAt'] = $result[2];
        $data['updatedAt'] = $result[3];
        $data['deletedAt'] = $result[4];
        return $data;
    }

    private function get($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Expected string as key');
        }

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    private function _exportSingle($data, $output)
    {
        if($output === 'document') {
            return new Document($this, $data);
        } else if($output === 'json') {
            return json_encode($data);
        }
        return $data;
    }

    private function getAll()
    {
        $stmt = self::$db->prepare('SELECT * FROM ' . $this->name);
        $stmt->execute();

        if(!is_array($this->data)) {
            $this->data = array();
        }

        while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
            $data = self::_arrayFromResult($row);
            $this->data[$data['_id']] = $data;
        }
        return $this->data;
    }

    private function _set($data)
    {
        $key = $data['_id'];
        $createdAt = date('c', strtotime($data['createdAt']));
        $updatedAt = date('c', strtotime($data['updatedAt']));
        $deletedAt = $data['deletedAt'];
        if($deletedAt) {
            $deletedAt = date('c', strtotime($data['deletedAt']));
        }

        unset($data['_id']);
        unset($data['createdAt']);
        unset($data['updatedAt']);
        unset($data['deletedAt']);

        $data = convertDate($data);

        $data = json_encode($data);

        if (isset($this->data[$key])) {
            $queryString ='UPDATE ' . $this->name . ' SET ';
            $queryString.= $this->valueColumnName . ' = :value, ';
            $queryString.= ' createdAt = :createdAt,';
            $queryString.= ' updatedAt = :updatedAt,';
            $queryString.= ' deletedAt = :deletedAt where ';
            $queryString.= $this->keyColumnName . ' = :key;';
        } else {
            $queryString = 'INSERT INTO ' . $this->name . ' VALUES (:key, :value, :createdAt, :updatedAt, :deletedAt);';
        }

        $stmt = self::$db->prepare($queryString);
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->bindParam(':value', $data, PDO::PARAM_STR);
        $stmt->bindParam(':createdAt', $createdAt, PDO::PARAM_STR);
        $stmt->bindParam(':updatedAt', $updatedAt, PDO::PARAM_STR);
        $stmt->bindParam(':deletedAt', $deletedAt);
        $stmt->execute();
        $this->data[(string)$key] = json_decode($data, true);
        Poller::setUpdated(static::$this->name);
        return $this->data[$key];
    }


    private function delete($key)
    {
        $db = (isset(self::$db)) ? self::$db : Model::$db;
        $stmt = $db->prepare(
            'DELETE FROM ' . $this->name . ' WHERE ' . $this->keyColumnName
            . ' = :key;'
        );
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        unset($this->data[$key]);
        Poller::setUpdated(static::$this->name);
    }

    /************************************************* PUBLIC START ***************************************************/

    public static function setConnection($pdoString)
    {
        self::$db = new PDO($pdoString);
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function create($data = array())
    {
        return new Document(self::getInstance(), $data);
    }

    public static function find($id, $output = 'document')
    {
        $Instance = self::getInstance();
        if(isset($Instance->data[$id])) {
            return $Instance->_exportSingle($Instance->data[$id], $output);
        }
        return null;
    }

    public function first($output = 'document')
    {
        if(count($this->data) > 0) {
            reset($this->data);
            $data = current($this->data);
            return $this->_exportSingle($data, $output);
        }
        return null;
    }

    public function last($output = 'document')
    {
        if(count($this->data) > 0) {
            end($this->data);
            $data = current($this->data);
            reset($this->data);
            return $this->_exportSingle($data, $output);
        }
        return null;
    }

    /* -1 = down up */

    public static function sort($by, $dir = -1) {
        if(is_callable($by)) {
            $fn = $by;
        } else {
            $fn = function($a, $b) use ($by, $dir) {
                if(!isset($b[$by]) || !isset($a[$by])) {
                    return 0;
                }
                if($dir === 1) {
                    return strcmp($a[$by], $b[$by]);
                } else {
                    return strcmp($b[$by], $a[$by]);
                }
            };
        }
        $Instance = self::getInstance();
        uasort($Instance->data, $fn);
        return $Instance;
    }

    public function fetch($output = 'document')
    {
        $data = $this->data;
        $return = array();
        foreach ($data as $d) {
            switch ($output) {
                case 'document':
                    $return[] = new Document($this, $d);
                    break;
                default:
                    $return[] = $d;
                    break;
            }
        }
        if($output === 'json') {
            $return = json_encode($return);
        }
        return $return;
    }

    public static function import($data, $overwrite = false, $truncate = false)
    {
        $isJson = false;

        if(is_string($data)) {
            $data = json_decode($data, true);
            $isJson = true;
        } else if(is_array($data)) {
            if(!isset($data[0])) {
                $data = [$data];
            }
        }

        if(is_null($data)) {
            throw new Error('Invalid data or JSON String!');
        }

        $result = array();
        foreach ($data as $d) {
            $item = new Document(self::getInstance(), $d);
            $item->save();
            $result[] = $item->fetch();
        }

        return ($isJson) ? json_encode($result) : $result;
    }

    public static function all($output = 'Instance') {
        $Instance = self::getInstance();
        $Instance->getAll();
        if($output !== 'Instance'){
            return $Instance->fetch($output);
        }
        return $Instance;
    }

    public function _insertOrUpdate($DocumentInstance)
    {
        $data = $DocumentInstance->fetch();
        $this->_set($data);
        return $DocumentInstance;
    }

    public function _delete($id)
    {
        $Instance = self::getInstance();
        $Instance->delete($id);
    }

    public static function flush()
    {
        $Instance = self::getInstance();
        $stmt = self::$db->prepare('DELETE FROM ' . $Instance->name);
        $stmt->execute();
        $Instance->data = array();
        Poller::setUpdated($Instance->name);
    }

    public static function length()
    {
        $Instance = self::getInstance();
        return (int) count($Instance->data);
    }

    public static function filter($path = '*', $operation = '===', $value = null)
    {
        if(!in_array($operation, array('===','<=','>=','<','>','!==', '==', '!='))) {
            $value = $operation;
            $operation = '===';
        }
        $Instance = self::getInstance();
        $Instance->getAll();
        $paths = explode(';', $path);
        $i = 1;

        $found = array();
        foreach ($Instance->data as $key => $item) {
            $now = $item;
            $i = 1;
            foreach ($paths as $path) {
                switch ($path) {
                    case preg_match('/^[0-9]+$/', $path) === 1 :
                        if(is_array($now)) {
                            $values = array_values($now);
                            if(isset($values[$path])) {
                                $now = $values[$path];
                                if($i === count($paths)) {
                                    if(!is_callable($value)) {
                                        if (compareOperator($now, $value, $operation)){
                                            $found[$key] = $item;
                                        }
                                    } else {
                                        if($value($now, $item, $key) === true) {
                                            $found[$key] = $item;
                                        }
                                    }
                                }
                            } else {
                                $now = null;
                            }
                        }
                        break;
                    case '*':
                    default:
                        if(isset($now[$path])) {
                            $now = $now[$path];
                        }
                        else if(is_array($now)) {
                            if(!array_key_exists('createdAt', $now)) {
                                $__ = new __();
                                $now = $__->pluck($now, $path);
                            }
                        }
                        if($i === count($paths)) {
                            if(!is_callable($value)) {
                                if(is_array($now)) {
                                    foreach ($now as $string) {
                                        if(compareOperator($string, $value, $operation)) {
                                            $found[$key] = $item;
                                        }
                                    }
                                } else {
                                    if(compareOperator($now, $value, $operation)) {
                                        $found[$key] = $item;
                                    }
                                }
                            } else {
                                if($value($now, $item, $key) === true) {
                                    $found[$key] = $item;
                                }
                            }
                        }
                        break;
                }
                $i++;
            }
        }
        $Instance->data = $found;
        return $Instance;
    }

    public static function filterOne($path = '*', $operation = '===', $value = null)
    {
        if(!in_array($operation, array('===','<=','>=','<','>','!==', '==', '!='))) {
            $value = $operation;
            $operation = '===';
        }
        return self::filter($path, $operation, $value)->first();
    }

    public static function getInstance() {
        if(!static::$tableName) {
            throw new RuntimeException('Invalid Table. Set a new property to this class: protected static $tableName = "TableName"');
        }

        if(!is_array(self::$Instance)) {
            self::$Instance = array();
        }
        if(!isset(self::$Instance[static::$tableName])) {
            self::$Instance[static::$tableName] = new static(static::$tableName);
        }
        return self::$Instance[static::$tableName];
    }

    public function __construct($name)
    {
        $db = self::$db;
        if(!$db) {
            throw new RuntimeException('Invalid DB. Run : Model::setConnection($PDOConnString) First.');
        }
        $this->data = array();
        $this->name = $name;
        $this->createTable();
        $this->getAll();
    }
}