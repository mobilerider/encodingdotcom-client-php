<?php

function as_array(\MobileRider\Encoding\Generics\DataHolderInterface $obj)
{
    return $obj->asArray();
}


function uid($regenerate = false)
{
    static $uid;

    if (!$uid || $regenerate) {
        $uid = uniqid();
    }

    return $uid;
}

function xstring($regenerate = false)
{
    return uid($regenerate);
}

// Taken from http://php.net/manual/en/class.simplexmliterator.php
function sxi_to_array(\SimpleXMLIterator $sxi)
{
    $a = array();

    for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {
        if(!array_key_exists($sxi->key(), $a)){
            $a[$sxi->key()] = array();
        }
        if($sxi->hasChildren()){
            $a[$sxi->key()][] = sxi_to_array($sxi->current());
        }
        else{
            $a[$sxi->key()][] = strval($sxi->current());
        }
    }
    return $a;
}
