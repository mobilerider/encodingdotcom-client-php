<?php

namespace MobileRider\Encoding\Event;

class ModelPropertyChangedEvent extends ModelEvent
{
    protected $oldValue;

    public function setOldValue($value)
    {
        if ($this->oldValue) {
            throw new \Exception('Value already set');
        }

        $this->oldValue = $value;
    }

    public function getOldValue()
    {
        return $this->oldValue;
    }
}

