<?php

namespace MobileRider\Encoding\Media;

class Source extends \MobileRider\Encoding\Generics\Model
{
    private $location;
    private $isExtended = false;

    private $_streams;

    public function __construct($location = '', array $streams = null, array $data = null)
    {
        $this->location = (string) $location;

        if ($streams) {
            $this->streams = $streams;
        }

        if ($data) {
            $this->setData($data);
        }
    }

    public function set($name, $value)
    {
        switch ($name) {
        case 'streams':
            $streams = $this->getStreams()->clear();
            $value = (array) $value;

            if ($value) {
                foreach ($value as $index => $stream) {
                    if (is_array($stream)) {
                        if (!is_numeric($index)) {
                            // $index => Type, $stream => Tracks
                            $stream = new Stream($index, $stream);
                        } else {
                            // $stream => Data
                            $stream = new Stream('', null, $stream);
                        }
                    }

                    $streams[] = $stream;
                }
            }
            break;
        default:
            return parent::set($name, $value);
        }
    }

    public function get($name)
    {
        switch ($name) {
        case 'streams':
            return array_map('as_array', $this->getStreams()->asArray());
        default:
            return parent::get($name);
        }
    }

    public function getStreams()
    {
        if (is_null($this->_streams)) {
            $this->_streams = new \MobileRider\Encoding\Generics\Collection();
            $this->_streams->setModelClass('\\MobileRider\\Encoding\\Media\\Stream');
        }

        return $this->_streams;
    }

    public function getData()
    {
        $data = parent::getData();

        $data['streams'] = $this->streams;

        return $data;
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
        $this->isExtended = $extended;
    }
}
