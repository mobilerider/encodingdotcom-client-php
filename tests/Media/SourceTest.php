<?php

use \MobileRider\Encoding\Media\Source;

class SourceTest extends \PHPUnit_Framework_TestCase
{
    private $location = 'http://test/audio.mp3';
    private $data = [
        "bitrate"  => "128k",
        "duration" => "108.66",
        "format"   => "mpeg audio",
        "filesize" => "1739320"
    ];
    private $streams = [
        "audio" => [
            1 => [
                "bitrate"     => "128k",
                "duration"    => "108",
                "codec"       => "mp3",
                "sample_rate" => "44100",
                "channels"    => "2"
            ]
        ]
    ];

    public function testConstruct()
    {
        // Allow empty creation
        $source = new Source();

        // Location
        $source = new Source($this->location);
        $this->assertEquals($this->location, (string) $source);
        $this->assertEquals($this->location, $source->getLocation());

        // Location and Streams
        $source = new Source($this->location, $this->streams);
        $this->assertEquals($this->location, $source->getLocation());
        $this->assertCount(1, $source->getStreams());

        // Location, Streams and Data
        $source = new Source($this->location, $this->streams, $this->data);
        $this->assertEquals($this->location, $source->getLocation());
        $this->assertCount(1, $source->getStreams());
        $this->assertCount(1 ,$source->streams);
        $this->assertCount(1 ,$source->getStreams()->first());
        $this->assertEquals($this->streams['audio'][1], $source->getStreams()->first()->first()->asArray());
        $this->assertEquals($this->streams['audio'][1], $source->streams[0][0]->asArray());

        $data = $source->asArray();

        $this->assertArrayHasKey('streams', $data);
        unset($data['streams']);
        $this->assertEquals($this->data, $data);
    }
}
