<?php

namespace Encoding;

use Encoding\Media\Parser;

class Queue implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable
{
    const LIST_NEW = 'new';
    const LIST_ADDED = 'added';
    const LIST_ENCODING = 'encoding';
    const LIST_DONE = 'done';
    const LIST_ERROR = 'error';

    protected $client = null;

    // New medias, exist only locally
    private $new = [];
    // Active medias
    private $added = [];
    private $encoding = [];
    private $done = [];
    private $error = [];

    // Maps media IDs to the list where it is stored
    private $indexing = [];

    private $options = [];


    public function __construct(Client $client, array $medias = null, array $options = null)
    {
        $this->client = $client;

        if ($medias) {
            $this->addMedias($medias);
        }

        if ($options) {
            $this->setOptions($options);
        }
    }

    // Iterates over active medias in the queue
    public function getIterator()
    {
        return new \ArrayIterator($this->encoding);
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->process($value);
        } else {
            $this->process($value, $offset);
        }
    }

    public function offsetExists($offset) {
        // Check if media exists in encoding list
        return $this->isEncoding($offset);
    }

    public function offsetUnset($offset)
    {
        // Cancel encoding media
        $this->cancel($offset);
    }

    public function offsetGet($offset)
    {
        return $this->findMedia($offset, self::LIST_ENCODING);
    }

    public function count()
    {
        return $this->countMedias(self::LIST_ENCODING);
    }

    public function serialize()
    {
        $data = [
            'lists' => [
                'added' => array_map($this->added, serialize),
                'encoding' => array_map($this->encoding, serialize),
                'error' => array_map($this->error, serialize)
            ],
            'options' => $this->options
        ];

        return serialize($this->getPersistentData());
    }

    public function unserialize($data)
    {
        $data = unserialize($data);

        foreach ($data['lists'] as $list => $medias) {
            foreach ($medias as $media) {
                try {
                    $this->setMedia(unserialize($media));
                } catch (\Exception $ex) {
                    // TODO
                    throw $ex;
                }
            }
        }
    }

    public function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    public function getOptions()
    {
        return $this->options;
    }

    // Receives only media object
    public function isScheduled(Media $media)
    {
        return array_search($media, $this->new);
    }

    protected function resolveId($value)
    {
        $isObject = is_object($value);

        return [$isObject ? ($value->isNew() ? $value->getId() : null) : $value, $isObject];
    }

    protected function getList($media)
    {
        $index = null;
        $listName = null;

        list($id, $isObject) = $this->resolveId($media);

        if ($id) {
            if ($listName = array_key_exists($id, $this->indexing) ? $this->indexing[$id] : null) {
                $index = $id;
            }
        } else if ($isObject) {
            $i = $this->isScheduled($media);

            if ($index !== false) {
                $listName = self::LIST_NEW;
                $index = $i;
            }
        }

        return [$listName, $index];
    }

    protected function exists($media)
    {
        return (bool) $this->getList($media);
    }

    protected function findMedia($id, $listName = null)
    {
        if (!$listName) {
            list($listName, $index) = $this->getList($id);

            return $listName ? $this->$listName[$index] : null;
        }

        return array_key_exists($id, $this->$listName) ? $this->$listName[$id] : null;
    }

    protected function setMedia(Media $media)
    {
        $oldListName = false;

        if ($media->isNew()) {
            if ($this->isScheduled($media) === false) {
                $this->new[] = $media;
            }
            $listName = self::LIST_NEW;
        } else {
            // Removes media from any current list
            $oldListName = $this->unsetMedia($media);

            if ($media->isEncoding()) {
                $listName = self::LIST_ENCODING;
            } else if ($media->isError()) {
                $listName = self::LIST_ERROR;
            } else if ($media->isDone()) {
                $listName = self::LIST_DONE;
            } else {
                $listName = self::LIST_ADDED;
            }

            // Puts media in corresponding list indexed by id
            $this->{$listName}[$media->getId()] = $media;

            // Maps this media to the list where was just added
            $this->indexing[$media->getId()] = $listName;
        }

        return [$listName, $oldListName];
    }

    private function unsetMedia(Media $media, $cancel = true)
    {
        list($listName, $index) = $this->getList($media);

        if (!$listName) {
            return false;
        }

        // Remove media from current list
        return array_splice($this->$listName, $index, 1);
    }

    public function execute($action, Media $media, array $params = null)
    {
        if ($media->isNew()) {
            if ($action != ACTION_MEDIA_ADD_MEDIA && $action != ACTION_MEDIA_ADD_MEDIA_BENCHMARK) {
                throw new Exception('Media ID needs to be specified');
            }
        } else {
            $params = [
                'mediaid' => $media->getId()
            ];

            $params += $params;
        }

        return $this->client->requestAction($action, $params);
    }

    public function validate(Media $item)
    {

    }

    protected function notifyStatusChange($media, $oldStatus)
    {

    }

    public function get($id)
    {
        if ($media = $this->findMedia($id)) {
            return $media;
        }

        // Get remote media
        // Assume media exists and try to refresh its status
        $media = new Media();
        $media->initialize($id);

        try {
            $this->getStatus($media);
        } catch (WrongMediaIdException $ex) {
            return null;
        }

        return $media;
    }

    public function process(Media $media)
    {
        if ($media->isEncoding()) {
            return [false, 'Media is already being processed'];
        }

        // Check for errors first since a media may be done
        // but contains errors in its formats encoding
        if ($media->hasError()) {
            if ($media->isError()) {
                return $this->restart($media);
            } else {
                return $this->restartFormatsWithError($media);
            }
        }

        if ($media->isDone()) {
            return [false, 'Media was already processed'];
        }

        $oldStatus = $media->getStatus();

        if ($this->media->isOnHold()) {
            // Start encoding on a media previously added to encoding queue (prepared)
            $action = Action::send(Action::ProcessMedia, $media);
        } else {
            $action = Action::send(Action::AddMedia, $media);
        }

        // Switch media list according to new status
        $this->setMedia($media);

        // Notify this media's status changed
        $this->notifyStatusChange($media, $oldStatus);

        return $action->isSuccessful();
    }

    public function addMedias($medias, $process = true)
    {
        foreach ($medias as $media) {
            $this->add($media);
        }
    }

    public function add(Media $media, $process = true)
    {
        if ($process) {
            return $this->process($media);
        }

        if (!$media->isNew()) {
            return [false, 'Media was already added'];
        }

        $action = Action::send(Action::AddMediaBenchmark, $media);
    }

    public function schedule(Media $media)
    {
        $this->setMedia($media);
    }

    public function prepare(Media $media)
    {
        $this->add($media, false);
    }

    public function cancel(Media $media)
    {

    }

    // Add if needed and process media
    public function processMedia(Media $media)
    {
        $data = [
            'source' => ''
        ];

        if ($this->hasSources()) {
            if ($this->hasMultipleSources()) {
                $data['source'] = [];

                foreach ($this->sources as $source) {
                    $data['source'][] = $source->getLocation();
                }
            } else {
                $data['source'] = $this->getSources()[0]->getLocation();
            }
        }

        $data['format'] = [];

        foreach ($this->formats as $format) {
            $format = array_merge([
                'output' => $format->getOutput()
            ], $format->getOptions());

            $data['format'][] = $format;
        }
    }

    public function getSourcesInfo($media, $extended = false)
    {
        if ($extended) {
            $name = ACTION_MEDIA_GET_MEDIA_INFO_EX;
        } else {
            $name = ACTION_MEDIA_GET_MEDIA_INFO;
        }

        $response = $this->execute($name, $media);

        if ($response === false) {
            return false;
        }

        $data = Parser::parseMediaInfo($response);

        foreach ($data['sources'] as $sourceData) {
            $sources = $media->getSources();

            foreach ($sources as $index => $source) {
                $source->update($sourceData['properties']);

                foreach ($sourceData['streams'] as $type => $tracks) {
                    foreach ($tracks as $id => &$track) {
                        $track = new \Encoding\Media\Track($id, $type, $track);
                    }

                    $source->setStream(new \Encoding\Media\Stream($type, array_values($tracks)));
                }
            }
        }

        return $response;
    }

    public function getStatus(Media $media, $extended = false)
    {
        $response = $this->execute(ACTION_MEDIA_GET_STATUS, $media);

        if ($response === false) {
            return false;
        }

        $data = Parser::parseMediaStatus($response);

        if ($media->getId() != $data['id']) {
            throw new Exception('Media Ids do not match');
        }

        $media->clearSources();

        foreach ($data['sources'] as $location) {
            $source = new \Encoding\Media\Source($location);

            $media->addSource($source);
        }

        $media->clearFormats();

        foreach ($data['formats'] as $formatData) {
            $format = new \Encoding\Media\Format($formatData['output'], $formatData['destinations'], $formatData['options']);
            $format->initialize($formatData['id'], $formatData['properties']);

            $media->addFormat($format);
        }

        if (isset($data['options'])) {
            $media->setOptions($data['options']);
        }

        $media->update($data['properties']);

        return $response;
    }

    public function getStatusAll($extended)
    {

    }
}
