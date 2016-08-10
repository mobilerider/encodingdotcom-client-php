<?php

/**
 * Class Queue
 */
namespace MobileRider\Encoding;

use MobileRider\Encoding\Generics\DataProxy;
use \MobileRider\Encoding\Media\Parser;
use \MobileRider\Encoding\Event\ModelEvent;
use \MobileRider\Encoding\Event\ModelPropertyChangedEvent;
use \MobileRider\Encoding\Status;

use \MobileRider\Encoding\Exception\EncodingException;

class Queue extends \MobileRider\Encoding\Generics\IndexedCollection
{
    const EVENT_MEDIA_ADDED_TO_QUEUE = 'event-media-added-to-queue';
    const EVENT_MEDIA_STATUS_CHANGED = 'event-media-status-changed';
    const EVENT_MEDIA_PROGRESS_CHANGED = 'event-media-progress-changed';
    const EVENT_MEDIA_ERROR = 'event-media-error';

    protected $client;
    protected $eventDispatcher;

    private $options;

    public function __construct(Client $client, array $medias = null, array $options = null)
    {
        $this->client = $client;

        $this->initialize();

        if ($options) {
            $this->setOptions($options);
        }

        if ($medias) {
            $this->add($medias);
        }
    }

    protected function initialize()
    {
        $this->eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
        $this->setModelClass('\\Mobilerider\\Encoding\\Media');
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function getOptions()
    {
        if (is_null($this->options)) {
            $this->options = new DataProxy();
        }

        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->getOptions()->setData($options);

        return $this;
    }

    public function get($index)
    {
        if ($this->has($index)) {
            return parent::get($index);
        }

        list($media, $msg) = $this->loadMedia($index);

        if ($media) {
            // Include just loaded media in this queue
            // Important to use parent class `set` otherwise
            // media may be re-processed
            parent::set($media);
        }

        return $media;
    }

    protected function loadMedia($id)
    {
        try {
            $media = new Media();
            $media->id = $id;

            $this->getStatus($media);

            return [$media, OK];
        } catch (WrongMediaIdException $ex) {
            return [null, $ex->getMessage()];
        }
    }

    private function triggerMediaEvent($media, $eventType)
    {
        $modelEvent = new ModelEvent();
        $modelEvent->setObject($media);
        $this->eventDispatcher->dispatch(
            self::EVENT_MEDIA_ADDED_TO_QUEUE, $modelEvent
        );
    }

    protected function updateMedia(Media $media, array $data, $extended = false)
    {
        $oldStatus = $media->status;
        $oldProgress = $media->progress;

        $media->update($data, $extended);

        if ($media->isNew()) {
            $this->triggerMediaEvent($media, self::EVENT_MEDIA_ADDED_TO_QUEUE);
        }

        if ($oldStatus != $media->status) {
            $propertyEvent = new ModelPropertyChangedEvent();
            $propertyEvent->setObject($media);
            $propertyEvent->setOldValue($oldStatus);

            $this->eventDispatcher->dispatch(self::EVENT_MEDIA_STATUS_CHANGED, $propertyEvent);
        }

        if ($oldProgress != $media->progress) {
            $propertyEvent = new ModelPropertyChangedEvent();
            $propertyEvent->setObject($media);
            $propertyEvent->setOldValue($oldStatus);

            $this->eventDispatcher->dispatch(self::EVENT_MEDIA_PROGRESS_CHANGED, $propertyEvent);
        }

        if (!$media->isQueued()) {
            parent::remove($media);
        }
    }

    protected function resolveMedia($seed)
    {
        if (is_numeric($seed)) {
            return $this->get($seed);
        }

        if ($seed instanceof Media) {
            return $seed;
        }

        return null;
    }

    protected function resolveMediaId($seed)
    {
        if (is_numeric($seed)) {
            return $seed;
        }

        return $seed->getId();
    }

    public function remove($media)
    {
        $id = $this->resolveMediaId($media);

        $this->cancelMedia($id);

        return parent::remove($id);
    }

    protected function cancelMedia($media)
    {
        if (is_object($media) && !$media->isQueued()) {
            return [false, 'Media is not queued'];
        }

        $id = $this->resolveMediaId($media);

        return $this->execute(ACTION_MEDIA_CANCEL_MEDIA, $media);
    }

    public function execute($action, $media = null, array $params = [])
    {
        if ($media) {
            if (!is_array($media) && $media->isNew()) {
                if ($action != ACTION_MEDIA_ADD_MEDIA && $action != ACTION_MEDIA_ADD_MEDIA_BENCHMARK) {
                    throw new Exception('Media ID needs to be specified');
                }
            } else {
                $mediaId = is_array($media) ? implode(',', array_map(
                    function($m) { return $m->getId(); }, $media
                )) : $media->getId();

                $params['mediaid'] = $mediaId;
            }
        }

        if (!$this->client) {
            throw new EncodingException('Client must be provided');
        }

        return $this->client->requestAction($action, $params);
    }

    public function validate(Media $item)
    {

    }

    public function subscribe(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
)
    {
        $this->eventDispatcher->addSubscriber($subscriber);
    }


    public function loadAllMedias()
    {
        $response = $this->execute(ACTION_MEDIA_GET_MEDIA_LIST);

        $data = Parser::parseMediaList($response);

        foreach ($data as $mediaData) {
            $media = parent::get($mediaData['id']);

            if (!$media) {
                $media = new Media();
            }

            $this->updateMedia($media, $mediaData, false);
        }

        return $response;
    }

    //public function __()
    //{
        // Check for errors first since a media may be done
        // but contains errors in its formats encoding
        //if ($media->hasError()) {
            //if ($media->isError()) {
                //return $this->restart($media);
            //} else {
                //return $this->restartFormatsWithError($media);
            //}
        //}

        //if ($media->isDone()) {
            //return [false, 'Media was already processed'];
        //}

        //$oldStatus = $media->status();

        //if ($this->media->isOnHold()) {
            //// Start encoding on a media previously added to encoding queue (prepared)
            //$action = Action::send(Action::ProcessMedia, $media);
        //} else {
            //$action = Action::send(Action::AddMedia, $media);
        //}

        //// Switch media list according to new status
        //$this->setMedia($media);
    //}

    protected function setMediaError($media, $errorMsg)
    {
        $oldStatus = $media->status();

        $media->setError($errorMsg);

        $modelEvent = new ModelEvent();
        $modelEvent->setObject($media);

        $this->eventDispatcher->dispatch(self::EVENT_MEDIA_ERROR, $modelEvent);

        if ($oldStatus != $media->status()) {
            $this->eventDispatcher->dispatch(self::EVENT_MEDIA_STATUS_CHANGED, $modelEvent);
        }
    }

    protected function checkMediaType($media, $silent = true)
    {
        if (!($media instanceof Media)) {
            if ($silent) {
                return false;
            } else {
                throw new \RuntimeException('Media incorrect type');
            }
        }

        return true;
    }

    public function set($index, $value = null)
    {
        // Ignore "index"
        if (func_num_args() == 1) {
            $media = $index;
        } else {
            $media = $value;
        }

        $this->checkMediaType($media);

        // Do nothing if is already in the queue
        if (!$media->isQueued()) {
            list($resp, $msg) = $this->process($media);

            if (!$resp) {
                throw new EncodingException($msg);
            }
        }
        // Add media to the local queue instance
        return parent::set($media);
    }

    public function process(Media $media, $encode = true)
    {
        try {
            if (!$media->getSources()->isEmpty()) {
                [false, 'Media does not contain any source'];
            }

            $params = $media->getPreparedParams();

            // Check if media is supoused to be just added or also processed
            if ($media->isOnHold()) {
                if ($media->isQueued()) {
                    return [false, 'Media is already in the queue'];
                }

                $response = $this->execute(ACTION_MEDIA_ADD_MEDIA_BENCHMARK, $media, $params);
            } else {
                if ($media->getFormats()->isEmpty()) {
                    throw new EncodingException('Media must have at least one format');
                }

                $response = $this->execute(ACTION_MEDIA_ADD_MEDIA, $media, $params);
            }

            if ($response === false) {
                // TODO: get actual encoding error
                throw new EncodingException('Request error');
            }

            // Check if media is already in the queue in which case
            if ($media->isQueued()) {
                // assume media was already ready and goes into processing status right away otherwise
                $this->updateMedia($media, array_merge(
                    ['status' => Status::STATUS_PROCESSING], $response
                ));
            } else {
                // media was just added to the queue and holds initial status: `New`
                $this->updateMedia($media, [
                    'id' => $response['MediaID'],
                    'status' => Status::STATUS_NEW
                ]);
            }
        } catch (EncodingExceptionInterface $ex) {
            $this->setMediaError($media, $ex->getMessage());
            return [false, $ex->getMessage()];
        }

        return [$response, OK];
    }

    public function cancel(Media $media)
    {

    }

    public function getSourcesInfo(Media $media, $extended = false)
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

        if (!$data) {
            return false;
        }

        $media->sources = $data;

        return $response;
    }

    public function getStatus(Media $media, $extended = false)
    {
        $response = $this->execute(ACTION_MEDIA_GET_STATUS, $media);

        if ($response === false) {
            return false;
        }

        // Returns multiple media items
        $data = Parser::parseMediaStatus($response);

        if (!$data) {
            return false;
        }

        $this->updateMedia($media, $data[0], $extended);

        return $response;
    }

    public function statusAll()
    {
        if (!$this->encoding) {
            return false;
        }

        // IMPORTANT: requesting status for more than one media need
        // always to be extended otherwise it will return just one media status
        $extended = true;

        $response = $this->execute(ACTION_MEDIA_GET_STATUS, $this->encoding, [
            'extended' => $extended ? 'yes' : 'no'
        ]);

        if ($response === false) {
            return false;
        }

        $data = Parser::parseMediaStatus($response, $extended); // Always extended

        if (!$data) {
            return false;
        }

        foreach ($data as $statusData) {
            $media = $this->findMedia($statusData['id'], self::LIST_ENCODING);
            $this->updateMedia($media, $statusData, $extended); // Always extended
        }

        return $response;
    }
}

