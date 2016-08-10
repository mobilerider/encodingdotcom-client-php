<?php

use \MobileRider\Encoding\Media\Format;

class FormatTest extends \PHPUnit_Framework_TestCase
{
    private $data = [
        'video_codec'       => 'libx264',
        'audio_codec'       => 'libfaac',
        'audio_sample_rate' => 44100,
        'audio_bitrate'     => '256k',
        'framerate'         => 30,
        'keyframe'          => 150,
        'profile'           => 'main'
    ];

    public function testConstruct()
    {
        // Allow empty creation
        $format = new Format('flv');

        $format = new Format('flv', null, $this->data);
        // Include output
        $data = $this->data;
        $data['output'] = 'flv';
        $this->assertEquals($data, $format->asArray());
    }
}

