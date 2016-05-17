<?php

namespace MobileRider\Encoding;

class Uploader
{
    private $client;
    private $formData;

    public function __construct($client, $sid = '')
    {
        $this->client = $client;
        $this->formData['sid'] = $sid;
    }

    public function hasSID()
    {
        return isset($this->formData['sid']);
    }

    public function getSID()
    {
        if (!$this->hasSID()) {
            return '';
        }

        return $this->formData['sid'];
    }

    public function generateFormData()
    {
        $data = [];

        $now = new \DateTime('now', new \DateTimeZone('Europe/London'));

        $data['timestamp'] = $now->format("Y-m-d H:i:s O");
        $data['sid'] = $this->client->getHostUserKeyHash();
        $data['signature'] = $this->client->createHash($data['timestamp'] . $data['sid']);
        $data['uid'] = $this->client->getUserId();

        return $this->formData = $data;
    }

    public function getFormData()
    {
        if (!isset($this->formData['timestamp'])) {
            $this->generateFormData();
        }

        return $this->formData;
    }

    public function getUploadUrl()
    {
        if (!$this->hasSID()) {
            $this->generateFormData();
        }

        return Service::API_URL . '/upload?X-Progress-ID=' . $this->getSID();
    }

    public function getServerUploadProgress()
    {
        list($response, $ok) = $client->get('/progress', [
            'query' => [ 'X-Progress-ID' => $this->getSID() ]
        ]);

        if (!$ok) {
            return [$response, false];
        }

        return $this->parseResponse($response);
    }

    public function getS3UploadProgress()
    {
        list($response, $ok) = $client->get('/s3info.php', [
           'query' => [ 'sid' => $this->getSID() ]
       ]);

        if (!$ok) {
            return [$response, false];
        }

        return $this->parseResponse($response);
    }

    public function getFileUrl()
    {
        list($response, $ok) = $client->get('/fileinfo.php', [
           'query' => [ 'sid' => $this->getSID() ]
       ]);

        if (!$ok) {
            return [$response, false];
        }

        return $this->parseResponse($response);
    }

    private function parseResponse($response)
    {
        $response = str_replace('new Object({', '{', $response);
        $response = str_replace(')}', '}', $response);

        $data = json_decode($response);

        if (!$data) {
            return [json_last_error_msg(), false];
        }

        return [$data, true];
    }
}
