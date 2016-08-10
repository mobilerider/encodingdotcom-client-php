<?php

namespace MobileRider\Encoding\Media;

class Track extends \MobileRider\Encoding\Generics\Model
{
    const TYPE_VIDEO = 'video-track-type';
    const TYPE_AUDIO = 'audio-track-type';
    const TYPE_IMAGE = 'image-track-type';
    const TYPE_TEXT = 'text-track-type';

    private $type;

    public function __construct($type, array $data = null)
    {
        $this->type = $type;

        if ($data) {
            $this->setData($data);
        }
    }
}
