<?php

namespace MobileRider\Encoding;

class Status
{
    const STATUS_NEW         = 'New';
    const STATUS_DOWNLOADING = 'Downloading';
    const STATUS_DOWNLOADED  = 'Downloaded';
    const STATUS_READY       = 'Ready to process';
    const STATUS_WAITING     = 'Waiting for encoder';
    const STATUS_PROCESSING  = 'Processing';
    const STATUS_SAVING      = 'Saving';
    const STATUS_FINISHED    = 'Finished';
    const STATUS_ERROR       = 'Error';
    const STATUS_STOPPED     = 'Stopped Perform';

    public static function validate($status)
    {
        static $all;

        // Only done first time
        if (is_null($all)) {
            $reflection = new \ReflectionClass(get_called_class());
            $all = $reflection->getConstants();
        }

        $status = strtolower($status);

        if (!in_array($status, $all)) {
            throw new \Exception('Invalid status: ' . $status);
        }

        return $status;
    }


}
