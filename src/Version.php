<?php

declare(strict_types=1);

namespace MailChannels;

final class Version
{
    public const VERSION = '1.3.0';

    public static function userAgent(): string
    {
        return 'mailchannels-php/' . self::VERSION;
    }
}
