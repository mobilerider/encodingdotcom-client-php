<?php

namespace MobileRider\Encoding;

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

    private function isJSON($response)
    {
        return $response->hasHeader(self::HEADER_CONTENT_TYPE) ? strpos($response->getHeader(self::HEADER_CONTENT_TYPE)[0], 'json') !== false : false;
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

        $payload = json_encode($payload);

        $response = \MobileRider\Encoding\Service::getHttpClient()->post('', array(
            // This will include needed content type
            // 'ContentType' => 'application/x-www-form-urlencoded'
            \GuzzleHttp\RequestOptions::FORM_PARAMS => array(
                'json' => $payload
            )
        ));

        if ($response->getStatusCode() >= 300) {
            throw new \Exception('Unsuccessful response: ' . $response->getReasonPhrase());
        }

        if (!$this->isJSON($response)) {
            throw new \Exception('JSON content type missing');
        }

        // Return json decoded array
        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['response']['errors'])) {
            throw new \Exception($data['response']['errors']['error']);
        }

        return $data['response'];
    }
}
