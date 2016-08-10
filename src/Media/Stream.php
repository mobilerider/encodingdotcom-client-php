<?php

namespace MobileRider\Encoding\Media;

class Stream extends \MobileRider\Encoding\Generics\Collection
{
    private $type;

    public function __construct($type, array $tracks = null, array $data = null)
    {
        $this->type = (string) $type;
        $this->setModelClass('\\MobileRider\\Encoding\\Media\\Track');

        if ($tracks) {
            foreach ($tracks as $track) {
                if (is_array($track)) {
                    $track = new Track($type, $track);
                }
                $this->add($track);
            }
        }

        //if ($data) {
            //$this->setData($data);
        //}
    }
}
