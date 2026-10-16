<?php

declare(strict_types=1);

namespace MailChannels;

final class Version
{
    public const VERSION = '2.2.1';

    public static function userAgent(): string
    {
        return 'mailchannels-php/' . self::VERSION;
    }
}
