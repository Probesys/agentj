<?php
// src/Message/CreateAlertMessage.php

namespace App\Message;

class CreateAlertMessage
{
    private string $mailId;

    public function __construct(string $mailId)
    {
        $this->mailId = $mailId;
        $this->log('CreateAlertMessage instantiated with mailId: ' . $mailId);
    }

    public function getMailId(): string
    {
        $this->log('getMailId called, returning: ' . $this->mailId);
        return $this->mailId;
    }

    private function log(string $message): void
    {
        error_log($message);
    }
}