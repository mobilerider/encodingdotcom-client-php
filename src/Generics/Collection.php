<?php

namespace MobileRider\Encoding\Generics;

class Collection implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable, DataHolderInterface
{
    private $modelClass = '';
    private $models = [];

    public function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->models);
    }

    public function get($index)
    {
        if (!isset($this[$index])) {
            return null;
        }

        return $this->models[$index];
    }

    public function set($index, $value)
    {
        if ($this->modelClass && !($value instanceof $this->modelClass)) {
            throw new \RuntimeException('Value is not instance of model ' . $this->modelClass);
        }

        if (is_null($index)) {
            $this->models[] = $value;
        } else {
            $this->models[$index] = $value;
        }

        return $this;
    }

    public function add($model)
    {
        return $this->set(null, $model);
    }

    public function concat(array $models)
    {
        foreach ($models as $model) {
            $this->set(null, $model);
        }

        return $this;
    }

    public function isEmpty()
    {
        return !count($this);
    }

    public function first()
    {
        if ($this->isEmpty()) {
            return null;
        }

        reset($this->models);

        return current($this->models);
    }

    public function asArray()
    {
        return $this->models;
    }

    public function clear()
    {
        $this->models = [];

        return $this;
    }

    public function has($index)
    {
        return array_key_exists($index, $this->models);
    }

    public function remove($index)
    {
        if (!$this->has($index)) {
            return null;
        }

        $model = $this->$index;

        unset($this->models[$index]);

        return $model;
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

    public function count()
    {
        return count($this->models);
    }

    public function serialize()
    {
        $data = [];

        foreach ($this->models as $index => $model) {
            $data[$index] = serialize($model);
        }

        return json_encode($data);
    }

    public function unserialize($data)
    {
        $models = json_decode($data, true);

        foreach ($models as $index => $model) {
            $this->set($index, unserialize($model));
        }
    }
}
