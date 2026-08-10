<?php

namespace MailChannels\Plugins\Symfony\RemoteEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\AbstractMailerEvent;

class TestEvent extends AbstractMailerEvent
{
    public const TEST = 'test';

    private string $message = '';

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
