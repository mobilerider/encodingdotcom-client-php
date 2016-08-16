<?php

namespace MobileRider\Encoding;

use \Guzzle\Http\Exception\TransferException;
use \MobileRider\Encoding\Service;

class Client
{
    const HEADER_CONTENT_TYPE = 'Content-Type';

    private $userId;
    private $userKey;

    public function __construct($userId, $userKey)
    {
        $this->userId = $userId;
        $this->userKey = $userKey;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getHostUserKeyHash()
    {
        return md5(uniqid($_SERVER['HTTP_HOST'] . $this->userKey, true));
    }

    public function createHash($data)
    {
        return hash("sha256", $data . $this->userKey);
    }

    private function isJSON($response)
    {
        $type = $response->getHeader(self::HEADER_CONTENT_TYPE);

        return $type ? strpos($type, 'json') !== false : false;
    }

    public function get($path, array $query = [])
    {
        try {
            $response = Service::getHttpClient()->get('progress', [
                'query' => $query
            ]);
        } catch (TransferException $ex) {
            // TODO: handle response code if any
            return [$ex->getMessage(), false];
        }

        if ($response->getStatusCode() >= 300) {
            return [$response->getReasonPhrase(), false];
        }

        return [$response->getBody()->getContents(), true];
    }

    public function requestAction($action, array $params, array $headers = array())
    {
        $payload = [
            'query' => [
                'userid'  => $this->userId,
                'userkey' => $this->userKey,
                'action'  => $action
            ]
        ];

        // Add the rest of the params, do not override the ones already set
        $payload['query'] += $params;
        // var_dump($payload);
        $payload = json_encode($payload);

        $response = Service::getHttpClient()->post('', [
            'ContentType' => 'application/x-www-form-urlencoded',
        ], ['json' => $payload])->send();

        if ($response->getStatusCode() >= 300) {
            throw new \Exception('Unsuccessful response: ' . $response->getReasonPhrase());
        }

        if (!$this->isJSON($response)) {
            throw new \Exception('JSON content type missing');
        }

        // Return json decoded array
        $data = json_decode((string)$response->getBody(), true);

        if (isset($data['response']['errors'])) {
            throw new \Exception($data['response']['errors']['error']);
        }
        //var_dump($data['response']);
        return $data['response'];
    }
}
