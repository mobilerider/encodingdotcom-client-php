<?php

namespace MobileRider\Encoding\Generics;

use \MobileRider\Encoding\Status;

class StatusModel extends Model
{
    public function isDownloading()
    {
        return $this->status == Status::STATUS_DOWNLOADING;
    }

    public function isReady()
    {
        return $this->status == Status::STATUS_READY;
    }

    public function isWaiting()
    {
        return $this->status == Status::STATUS_WAITING;
    }

    public function isProcessing()
    {
        return $this->status == Status::STATUS_PROCESSING;
    }

    public function isSaving()
    {
        return $this->status == Status::STATUS_SAVING;
    }

    public function isDone()
    {
        return $this->isFinished();
    }

    public function isFinished()
    {
        return $this->status == Status::STATUS_FINISHED;
    }

    public function isStopped()
    {
        return $this->status == Status::STATUS_STOPPED;
    }

    public function isError()
    {
        return $this->status == Status::STATUS_ERROR;
    }

    public function isOver()
    {
        return $this->isDone() || $this->isStopped() || $this->isError();
    }

    public function setError($msg)
    {
        $this->error = $msg;
        $this->status = Status::STATUS_ERROR;
    }
}
