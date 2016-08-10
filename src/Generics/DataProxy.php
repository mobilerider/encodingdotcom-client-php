<?php

namespace MobileRider\Encoding\Generics;

class DataProxy implements \ArrayAccess, \Serializable, DataHolderInterface
{
    private $data = [];

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        $this->remove($name);
    }

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function serialize()
    {
        return json_encode($data);
    }

    public function unserialize($data)
    {
        $this->data = json_decode($data, true);
    }

    public function has($name)
    {
        return array_key_exists($name, $this->data);
    }

    public function get($name)
    {
        if (!$this->has($name)) {
            return null;
        }

        return $this->data[$name];
    }

    public function set($name, $value)
    {
        if (!is_string($name)) {
            throw new \RuntimeException('Property name should be a string');
        }

        $this->data[$name] = $value;

        return $this;
    }

    public function remove($name)
    {
        if (!$this->has($name)) {
            return null;
        }

        $value = $this->get($name);

        unset($this->data[$name]);

        return $value;
    }

    public function setData(array $data)
    {
        if (!$data) {
            return false;
        }

        foreach ($data as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function asArray()
    {
        return $this->getData();
    }

    public function clear()
    {
        $this->data = [];

        return $this;
    }
}
