<?php


class Document
{
    private $parent;
    private $attributes;

    public function __set($var, $value)
    {
        if(!validPropertyName($var)) {
            throw new Exception("Document property names cannot contain the star '*' sign or semicolon ';'");
        }
        if(property_exists($this, $var)) {
            $this->$var = $value;
        } else {
            $this->attributes[$var] = $value;
        }
    }

    public function __get($var)
    {
        if(property_exists($this, $var)) {
            return $this->$var;
        } else {
            return $this->attributes[$var];
        }
    }

    public function structure()
    {
        $arr = array();
        foreach ($this->attributes as $key => $value) {
            $arr[$key] = gettype($value);
        }
        return $arr;
    }

    public function fetch($format = 'array')
    {
        return ($format === 'json') ? json_encode($this->attributes, true) : $this->attributes;
    }

    public function delete(){
        return $this->parent->_delete($this->_id);
    }

    public function save(){
        if(is_null($this->_id)) {
            $this->_id = Helpers::uuid_v4();
        }
        $this->updatedAt = date('c');
        return $this->parent->_insertOrUpdate($this);
    }

    public function __construct($parent, $data = array())
    {
        $this->parent = $parent;

        if(!isset($data['_id'])) {
            $this->_id = Helpers::uuid_v4();
        }

        if(is_string($data)) {
            $data = json_decode($data, true);
        }

        if($data === null) {
            throw new Exception('Invalid data or JSON String!');
        }

        if(!is_array($this->attributes)) {
            $this->attributes = array();
        }

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        if(!isset($data['createdAt'])) {
            $this->createdAt = date('c');
        }

        if(!isset($data['updatedAt'])) {
            $this->updatedAt = date('c');
        }

        if(!isset($data['deletedAt'])) {
            $this->deletedAt = null;
        }
    }
}