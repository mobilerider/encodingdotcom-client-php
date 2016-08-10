<?php

use \MobileRider\Encoding\Generics\DataProxy;

class DataProxyTest extends \PHPUnit_Framework_TestCase
{
    private $data = [
        "bitrate"  => "128k",
        "duration" => "108.66",
        "format"   => "mpeg audio",
        "filesize" => "1739320"
    ];

    public function testConstruct()
    {
        // Allow empty creation
        $obj = new DataProxy();
        $obj->testProperty = xstring(true);
        $this->assertEquals($obj->testProperty, xstring());

        $obj->set('testProperty', xstring(true));
        $this->assertEquals($obj->testProperty, xstring());

        $obj = new DataProxy();
        $obj->setData($this->data);
        $this->assertEquals($this->data, $obj->asArray());
    }
}

