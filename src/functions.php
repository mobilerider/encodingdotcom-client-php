<?php

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
