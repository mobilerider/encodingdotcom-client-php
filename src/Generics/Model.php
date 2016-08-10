<?php

namespace MobileRider\Encoding\Generics;

class Model extends DataProxy
{
    public function getId()
    {
        return $this->id;
    }

    public function isNew()
    {
        return !$this->getId();
    }
}
