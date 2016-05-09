<?php

namespace Encoding;

use Encoding\Media\Parser;

class Queue implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable
{
    const LIST_LOCAL = 'local';
    const LIST_ON_HOLD = 'onHold';
    const LIST_ENCODING = 'encoding';
    const LIST_DONE = 'done';
    const LIST_ERROR = 'error';

    protected $client = null;

    // New medias, exist only locally
    private $local = [];
    // Active medias
    private $onHold = [];
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
                'onHold' => array_map('serialize', $this->onHold),
                'encoding' => array_map('serialize', $this->encoding),
                'error' => array_map('serialize', $this->error)
            ],
            'options' => $this->options
        ];

        return serialize($data);
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
        return array_search($media, $this->local);
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
                $listName = self::LIST_LOCAL;
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
                $this->local[] = $media;
            }
            $listName = self::LIST_LOCAL;
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
                $listName = self::LIST_ON_HOLD;
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
        if ($media->isOnHold() && $process) {
            return $this->process($media);
        }

        try {
            if (!$media->isNew()) {
                throw new EncodingException('Media is already in the queue');
            }

            if (!$media->hasSources()) {
                throw new EncodingException('Media needs to have at least one source');
            }

            $formatsData = [];

            foreach ($media->getFormats() as $format) {
                $formatsData[] = array_merge([
                    'output' => $format->getOutput(),
                    'destination' => $format->getDestinations(),
                ], $format->getOptions());
            }

            $params = array_merge(
                ['source' => array_map('strval', $media->getSources())],
                array_merge($this->getOptions(), $media->getOptions())
            );

            if ($formatsData) {
                // Do not create empty format parameter
                $params['format'] = $formatsData;
            }

            if ($process) {
                if (!$media->hasFormats()) {
                    throw new EncodingException('Media must have at least one format');
                }

                $response = $this->execute(ACTION_MEDIA_ADD_MEDIA, $media, $params);
            } else {
                $response = $this->execute(ACTION_MEDIA_ADD_MEDIA_BENCHMARK, $media, $params);
            }

            if ($response === false) {
                // TODO: get actual encoding error
                $media->setError('Request error');
                throw new EncodingException('Request error');
            }

            $media->initialize($response['MediaID']);
        } catch (EncodingExceptionInterface $ex) {
            return [false, $ex->getMessage()];
        } finally {
            $this->setMedia($media);
        }

        return [$response, true];
    }

    public function schedule(Media $media)
    {
        $this->setMedia($media);
    }

    public function prepare(Media $media)
    {
        $this->add($media, false);
    }

    public function process(Media $media)
    {
        if (!$media->isOnHold() && $media->isReady()) {
            return [false, 'Media is not on hold or not ready to process'];
        }

        try {
            $response = $this->execute(ACTION_MEDIA_PROCESS_MEDIA, $media, $params);
            $media->setStatus(Media::STATUS_PROCESSING);
        } catch (EncodingExceptionInterface $ex) {
            return [false, $ex->getMessage()];
        } finally {
            $this->setMedia($media);
        }

        return $response;
    }

    public function cancel(Media $media)
    {

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

        $this->setMedia($media);

        return $response;
    }

    public function getStatusAll($extended)
    {

    }
}
