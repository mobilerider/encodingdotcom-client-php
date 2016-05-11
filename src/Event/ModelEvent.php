<?php

namespace MobileRider\Encoding\Event;

use Symfony\Component\EventDispatcher\Event;

class ModelEvent extends Event
{
    protected $object;

    public function setObject(\MobileRider\Encoding\Generics\DataItem $object)
    {
        if ($this->object) {
            throw new \Exception('Object already set');
        }

        $this->object = $object;
    }

    public function getId()
    {
        return $this->object->id();
    }

    public function getObject()
    {
        return $this->object;
    }
}

