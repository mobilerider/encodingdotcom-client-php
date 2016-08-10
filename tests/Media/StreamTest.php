<?php

use \MobileRider\Encoding\Media\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase
{
    private $tracks = [
        1 => [
            "bitrate"     => "128k",
            "duration"    => "108",
            "codec"       => "mp3",
            "sample_rate" => "44100",
            "channels"    => "2"
        ]
    ];

    public function testConstruct()
    {
        $stream = new Stream('audio');

        $stream = new Stream('audio', $this->tracks);
        $this->assertCount(count($this->tracks), $stream);
        $this->assertEquals($this->tracks[1], $stream->first()->asArray());
    }
}
