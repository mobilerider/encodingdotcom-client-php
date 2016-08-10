<?php

namespace MobileRider\Encoding;

class Service
{
    const API_URL = 'https://manage.encoding.com';

    private static $httpClient;

    private $user = null;

    # userId
    # userKey
    public function __construct($userId, $userKey)
    {
        $this->user = new User($userId, $userKey);
    }

    public static function getHttpClient()
    {
        if (!self::$httpClient) {
            self::$httpClient = new \GuzzleHttp\Client([
                // Base URI is used with relative requests
                'base_uri' => self::API_URL,
                //         // You can set any number of default request options.
                'timeout'  => 5.0,
            ]);
        }

        return self::$httpClient;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getQueue($userId = null)
    {
        if ($userId && $userId != $this->getUser()->getId()) {
            return $this->getUser()->getSubUser($userId)->getQueue();
        }

        return $this->getUser()->getQueue();
    }

    public function getMedia($id)
    {
        return $this->getQueue()->get($id);
    }

    public function prepare($source, array $formats = null, array $options = null)
    {
        $media = new Media($source, $formats, $options);
        // Sets media in hold so it won't be processed
        $media->hold();

        $this->getQueue()->add($media);

        return $media;
    }

    public function encode($source, array $formats, array $options = null)
    {
        $media = new Media($source, $formats, $options);
var_dump($media->getData());
        $this->getQueue()->add($media);

        return $media;
    }
}
