<?php

namespace MobileRider\Encoding;

use \MobileRider\Encoding\Generics\Collection;

class Media extends \MobileRider\Encoding\Generics\StatusModel
{
    private $_sources;
    private $_formats;
    private $options;

    private $isExtended = false;
    private $onHold = false;

    public function __construct($sources = null, array $formats = null, array $data = null)
    {
        if ($sources) {
            $this->sources = $sources;
        }

        if ($formats) {
            $this->formats = $formats;
        }

        if ($data) {
            $this->setData($data);
        }
    }

    public function set($name, $value)
    {
        switch ($name) {
        case 'source':
        case 'sources':
        case 'sourcefile':
            $this->setSources($value);
            break;
        case 'formats':
        case 'format':
            $this->setFormats($value);
            break;
        default:
            return parent::set($name, $value);
        }
    }

    public function get($name)
    {
        switch ($name) {
        case 'source':
        case 'sources':
        case 'sourcefile':
            return array_map('as_array', $this->getSources()->asArray());
        case 'formats':
        case 'format':
            return array_map('as_array', $this->getFormats()->asArray());
        default:
            return parent::get($name);
        }
    }

    public function getSources()
    {
        if (is_null($this->_sources)) {
            $this->_sources = new Collection();
            $this->_sources->setModelClass('\\MobileRider\\Encoding\\Media\\Source');
        }

        return $this->_sources;
    }

    public function setSources($value)
    {
        $sources = $this->getSources()->clear();
        $value = (array) $value;

        if ($value) {
            foreach ($value as $index => $source) {
                if (!$source) {
                    continue;
                }

                if (!is_object($source)) {
                    if (!is_numeric($index)) {
                        $location = $index;
                        $data = $source;
                    } else if (is_array($source)) {
                        $location = '';
                        $data = $source;
                    } else {
                        $location = $source;
                        $data = null;
                    }

                    $source = new Media\Source($location, null, $data);
                }

                $sources[] = $source;
            }
        }
    }

    public function getFormats()
    {
        if (is_null($this->_formats)) {
            $this->_formats = new Collection();
            $this->_formats->setModelClass('\\MobileRider\\Encoding\\Media\\Format');
        }

        return $this->_formats;
    }

    public function setFormats($value)
    {
        // Check whether value is directly a format or a list of formats
        $value = isset($value[0]) ? $value : [$value];
        $formats = $this->getFormats()->clear();

        if ($value) {
            foreach ($value as $index => $format) {
                if (!$format) {
                    continue;
                }

                if (is_array($format)) {
                    $output = !is_numeric($index) ? $index : null;
                    // $format => Data
                    $format = new Media\Format($output, null, $format);
                }

                $formats[] = $format;
            }
        }
    }

    public function isOnHold()
    {
        return $this->onHold;
    }

    public function isQueued()
    {
        return !$this->isNew() && !$this->isOver();
    }

    public function isEncoding()
    {
        return $this->isProcessing() || $this->isSaving();
    }

    public function isExtended()
    {
        return $this->isExtended;
    }

    public function isSourceExtended()
    {
        if ($this->getSources()->isEmpty()) {
            return false;
        }

        // Assume if one is extended all the others are
        // all the sources should be updated in the same request
        return $this->getSources()->first()->isExtended();
    }

    public function getData()
    {
        $data = parent::getData();
        $data['source'] = array_map('strval', $this->getSources()->asArray());
        $data['format'] = $this->formats;

        return $data;
    }

    public function getPreparedParams()
    {
        return $this->getData();
    }

    public function update(array $data, $extended = false)
    {
        $this->setData($data);
        $this->isExtended = $extended;

        return $this;
    }

    public function hold()
    {
        if (!$this->isQueued()) {
            return [false, 'Already in queue'];
        }

        $this->onHold = true;

        return [true, OK];
    }

    public function hasError()
    {
        if ($this->isError()) {
            return true;
        }

        foreach($this->getFormats() as $format) {
            if ($format->isError()) {
                return true;
            }
        }

        return false;
    }

    public function getVideoTrack()
    {

    }

    public function hasVideoTrack()
    {

    }

    public function getAudioTrack()
    {

    }

    public function hasAudioTrack()
    {

    }

    public function hasTextTrack()
    {

    }

    public function getTextTrack()
    {

    }
}
