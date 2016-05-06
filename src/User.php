<?php

namespace Encoding;

class User extends \Encoding\Generics\DataItem
{
    private $key = null;
    private $client = null;

    private $queue = null;
    private $subUsers = [];

    public function __construct($id, $key)
    {
        if (empty($id && $key)) {
            throw new Exception('Empty user Id or Key');
        }

        $this->initialize($id);
        $this->key = $key;

        $this->client = new \Encoding\Client($id, $key);
        $this->queue = new \Encoding\Queue($this->client);
    }

    public function getQueue()
    {
        return $this->queue;
    }
}
