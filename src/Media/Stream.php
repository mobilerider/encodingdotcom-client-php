<?php

namespace Encoding\Media;

class Stream implements \IteratorAggregate, \Countable
{
    private $type = null;
    private $tracks = [];

    public function __construct($type, array $tracks = null)
    {
        $this->type = $type;

        if ($tracks) {
            foreach ($tracks as $track) {
                $this->setTrack($track);
            }
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->tracks);
    }

    public function count()
    {
        return count($this->tracks);
    }

    public function getType()
    {
        return $this->type;
    }

    protected function setTrack(Track $track)
    {
        if (!$track->getId()) {
            throw new \Exception('Invalid Id');
        }

        $oldTrack = $this->getTrack($track->getId());

        $this->tracks[$track->getId()] = $track;

        return $oldTrack;
    }

    public function getTrack($id)
    {
        if (!$this->hasTrack($id)) {
            return null;
        }

        return $this->tracks[$id];
    }

    public function hasTrack($id)
    {
        return array_key_exists($id, $this->tracks);
    }

    public function clearTracks()
    {
        $this->tracks = [];
    }

    public function first()
    {
        if (!$this->tracks) {
            return null;
        }

        rewind($this->tracks);

        return current($this->tracks);
    }
}
