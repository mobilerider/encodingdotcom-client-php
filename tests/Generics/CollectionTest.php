<?php

use \MobileRider\Encoding\Generics\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $collection = new Collection();
        $this->assertCount(0, $collection);

        $collection->add(xstring(true));
        $this->assertCount(1, $collection);
        $this->assertEquals($collection[0], xstring());
    }
}
