<?php

namespace MobileRider\Encoding\Generics;

class DataItem
{
    private $id;
    private $data = [];
    private $cleanNames = [];

    // Support getters and setters
    public function __call($method, $args)
    {
        $type = strtolower(substr($method, 0, 3));

        // Not sure if allow setters since most of the data is
        // loaded from requests and set at the same time
        if ($type != 'get') {
            throw new \BadMethodCallException('Call to undefined method ' . $method);
        }

        $cleanName = lcfirst(substr($method, 3));

        if (!array_key_exists($cleanName, $this->cleanNames)) {
            return null;
        }

        return $this->get($this->cleanNames[$cleanName]);
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function has($property)
    {
        return array_key_exists($property, $this->data);
    }

    public function hasCleanProperty($property)
    {
        return array_key_exists($property, $this->cleanNames);
    }

    public function initialize($id, array $data = null)
    {
        if (!$this->isNew()) {
            throw new \Exception('Object already initialized');
        }

        $this->id = intval($id);

        if ($data) {
            $this->setData($data);
        }
    }

    public function isNew()
    {
        return !$this->getId();
    }

    public function getData()
    {
        return $this->data;
    }

    public function getCleanData()
    {
        $data = [];
        $dirtyNames = array_flip($this->cleanNames);

        foreach ($this->data as $name => $value) {
            $data[$dirtyNames[$name]] = $value;
        }

        return $data;
    }

    protected function sanitizeName($name)
    {
        $name = lcfirst($name);

        return preg_replace_callback('/[^A-Za-z0-9]+([A-Za-z0-9])/', function($s) {
            return ucfirst($s[1]);
        }, $name);
    }

    protected function registerCleanName($name)
    {
        $cleanName = $this->sanitizeName($name);
        // Not sure what to do with duplicates atm, for now don't override
        if (!$this->hasCleanProperty($cleanName)) {
            $this->cleanNames[$cleanName] = $name;
        }
    }

    protected function setData(array $data)
    {
        if (!$data) {
            return false;
        }

        $this->data = array_merge($this->data, $data);

        foreach ($data as $key => $_) {
            $this->registerCleanName($key);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    protected function set($property, $value)
    {
        $this->data[$property] = $value;

        $this->registerCleanName($property);
    }

    public function get($property)
    {
        if (!$this->has($property)) {
            return null;
        }

        return $this->data[$property];
    }

    public function clear()
    {
        $this->data = [];
        $this->cleanNames = [];
    }
}
