<?php


class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected $testInfoData = [
        "id"         => "12345678",
        "userid"     => "123",
        "status"     => "Ready to process",
        "created"    => "2016-04-26 03:46:35",
        "started"    => "2016-04-26 03:46:35",
        "finished"   => "0000-00-00 00:00:00",
        "downloaded" => "2016-04-26 03:46:45",
        "processor"  => "AMAZON",
        "region"     => "us-east-1",
        "sourcefile" => [
            "http://techslides.com/demos/sample-videos/small.mp4",
            "http://techslides.com/demos/sample-videos/small.mp4"
        ],
        "time_left"         => "20",
        "progress"          => "100.0",
        "time_left_current" => "0",
        "progress_current"  => "0.0",
        "queue_time"        => "0"
    ];

    public function testParseMediaInfo()
    {

    }
}
