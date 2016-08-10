<?php

use \MobileRider\Encoding\Media\Track;

class TrackTest extends \PHPUnit_Framework_TestCase
{
    private $data = [
        "bitrate"     => "128k",
        "duration"    => "108",
        "codec"       => "mp3",
        "sample_rate" => "44100",
        "channels"    => "2"
    ];

    public function testConstruct()
    {
        // Allow empty creation
        $track = new Track('audio');

        $track = new Track('audio', $this->data);
        $this->assertEquals($this->data, $track->asArray());
    }
}
