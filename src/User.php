<?php

namespace MobileRider\Encoding;

class User extends \MobileRider\Encoding\Generics\DataItem
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

        $this->client = new \MobileRider\Encoding\Client($id, $key);
        $this->queue = new \MobileRider\Encoding\Queue($this->client);
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function attachQueue($queue)
    {
        $this->queue = $queue;
        $this->queue->setClient($this->client);
    }
}

