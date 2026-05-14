<?php

declare(strict_types=1);

namespace MailChannels\Enum;

enum DkimUpdateStatus: string
{
    case Retired = 'retired';
    case Revoked = 'revoked';
    case Rotated = 'rotated';
}
