<?php

declare(strict_types=1);

namespace MailChannels\Enum;

enum CustomTrackingStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
