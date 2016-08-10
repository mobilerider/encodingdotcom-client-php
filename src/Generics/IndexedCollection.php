<?php

namespace MobileRider\Encoding\Generics;

class IndexedCollection extends Collection
{
    public function getIndexProperty()
    {
        return 'id';
    }

    public function set($index, $value = null)
    {
        if (func_num_args() == 1) {
            $value = $index;
            $index = null;
        }

        $property = $this->getIndexProperty();

        if (!is_object($value) || empty($value->$property)) {
            throw new \RuntimeException("Value needs to be an object with an `$property` property");
        }

        return parent::set($value->$property, $value);
    }

    public function remove($index)
    {
        if (is_object($index)) {
            $property = $this->getIndexProperty();
            $index = $index->$property;
        }

        return parent::remove($index);
    }
}
