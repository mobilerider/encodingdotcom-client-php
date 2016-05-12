<?php

namespace MobileRider\Encoding\Media;

class Format extends \MobileRider\Encoding\Generics\DataItem
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

        return $this;
    }

    public function clearOptions()
    {
        $this->options = [];
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : null;
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

    public function useVideoCodec($codec, $bitrate, array $settings = [])
    {
        return $this->setOptions(array(
            'video_codec' => $codec,
            'bitrate' => $bitrate,
            'video_codec_parameters' => $settings
        ));
    }

    public function useAudioCodec($codec, $bitrate, $channels = 2, $sampleRate = 44100, $volume = 100)
    {
        return $this->setOptions(array(
            'audio_codec' => $codec,
            'audio_bitrate' => $bitrate,
            'audio_channels' => $channels,
            'audio_sample_rate' => $sampleRate,
            'audio_volume' => $volume
        ));
    }

    public function withinBitrates($min, $max)
    {
        return $this->setOptions(array(
            'minrate' => $min,
            'maxrate' => $max
        ));
    }

    public function segment($start, $duration = null)
    {
        return $this->setOptions(array(
            'minrate' => $min,
            'maxrate' => $max
        ));
    }

    public function addMultipleOption($name, array $value)
    {
        if ($this->hasOption($name)) {
            $value = array_merge($this->getOption($name), $value);
        }

        return $this->setOptions(array(
            $name => implode(',', $value)
        ));
    }

    public function addBitrates(array $value)
    {
        return $this->setMultipleOption('bitrates', $value);
    }

    public function addFramerates(array $value)
    {
        return $this->setMultipleOption('framerates', $value);
    }

    public function addKeyframes(array $value)
    {
        return $this->setMultipleOption('keyframes', $value);
    }

    public function setSize($width, $height)
    {
        return $this->setOptions(array(
            'size' => $width . 'x' . $height
        ));
    }
}
