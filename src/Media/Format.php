<?php

namespace MobileRider\Encoding\Media;

class Format extends \MobileRider\Encoding\Generics\StatusModel
{
    public function __construct($output = '', $destinations = null, array $data = null)
    {
        if ($data) {
            $this->setData($data);
        }

        if ($output) {
            $this->output = $output;
        }

        if ($destinations) {
            $this->destination = (array) $destinations;
        }
    }

    public function useVideoCodec($codec, $bitrate, array $settings = [])
    {
        return $this->setData(array(
            'video_codec' => $codec,
            'bitrate' => $bitrate,
            'video_codec_parameters' => $settings
        ));
    }

    public function useAudioCodec($codec, $bitrate, $channels = 2, $sampleRate = 44100, $volume = 100)
    {
        return $this->setData(array(
            'audio_codec' => $codec,
            'audio_bitrate' => $bitrate,
            'audio_channels' => $channels,
            'audio_sample_rate' => $sampleRate,
            'audio_volume' => $volume
        ));
    }

    public function withinBitrates($min, $max)
    {
        return $this->setData(array(
            'minrate' => $min,
            'maxrate' => $max
        ));
    }

    public function segment($start, $duration = null)
    {
        return $this->setData(array(
            'minrate' => $min,
            'maxrate' => $max
        ));
    }

    public function addMultipleOption($name, array $value)
    {
        if (!$value) {
            return $this;
        }

        $existent = '';

        if ($this->getOptions()->has($name)) {
            $existent = $this->getOptions()->get($name);
        }

        $value = implode(',', array_filter('strlen', $value));

        if (!$value) {
            return $this;
        }

        return $this->setData(array(
            $name => $existent . ',' . $value
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
        return $this->setData(array(
            'size' => $width . 'x' . $height
        ));
    }
}
