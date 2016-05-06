<?php

namespace Encoding\Media;

class Format extends \Encoding\Generics\DataItem
{
    private $output = '';
    private $destinations = [];

    private $options = [];

    public function __construct($output = '', array $destinations = null, array $options = null)
    {
        $this->output = $output;
        $this->setOptions($options);

        if ($destinations) {
            foreach ($destinations as $index => $value) {
                if (is_numeric($index)) {
                    $destination = $value;
                    $status = '';
                } else {
                    $destination = $index;
                    $status = $value;
                }

                $this->setDestinationStatus($destination, $status);
            }
        }
    }

    public function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    public function clearOptions()
    {
        $this->options = [];
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setDestinationStatus($destination, $status = '')
    {
        $this->destinations[$destination] = $status;
    }

    public function getDestinations()
    {
        return array_keys($this->destinations);
    }

    public function hasMultipleDestinations()
    {
        return count($this->destinations) > 1;
    }
}
