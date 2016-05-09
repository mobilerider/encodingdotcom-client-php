<?php

namespace MobileRider\Encoding\Media;

class Track extends \MobileRider\Encoding\Generics\DataItem
{
    const TYPE_VIDEO = 'video-track-type';
    const TYPE_AUDIO = 'audio-track-type';
    const TYPE_IMAGE = 'image-track-type';
    const TYPE_TEXT = 'text-track-type';

    private $type = null;

    public function __construct($id, $type, array $data)
    {
        $this->type = $type;

        $this->initialize($id, $data);
    }

    public function getType()
    {
        return $this->type;
    }

}
