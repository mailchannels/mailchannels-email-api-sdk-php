<?php

declare(strict_types=1);

namespace MailChannels\Enum;

enum DkimKeyStatus: string
{
    case Active = 'active';
    case Retired = 'retired';
    case Revoked = 'revoked';
    case Rotated = 'rotated';
}
