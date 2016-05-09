<?php

namespace MobileRider\Encoding\Media;

class Source extends \MobileRider\Encoding\Generics\DataItem
{
    private $location = '';
    private $isExtended = false;

    private $options = [];
    private $streams = [];

    public function __construct($location, array $options = null)
    {
        $this->setLocation($location);
    }

    private function setLocation($location)
    {
        $this->location = $location;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function __toString()
    {
        return $this->getLocation();
    }

    public function update(array $data, $extended = false)
    {
        $this->setData($data);

        if ($this->getSize()) {
            list($width, $height) = explode('x', $this->getSize());
            $this->set('width', $width);
            $this->set('height', $height);
        }

        $this->isExtended = $extended;
    }

    public function isExtended()
    {
        return $this->isExtended;
    }

    public function setStream(Stream $stream)
    {
        $this->streams[$stream->getType()] = $stream;
    }

    public function createStream($type, array $tracks = null)
    {
        $this->setStream(new Stream($type, $tracks));
    }

    public function clearStreams()
    {
        $this->streams = [];
    }

    public function getBitrateNumber()
    {
        return intval(rtrim($this->getBitrate(), 'k'));
    }
}
