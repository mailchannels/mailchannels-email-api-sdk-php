<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use MailChannels\Response;

final class Usage extends AbstractResource
{
    public function retrieve(): Response
    {
        return $this->request('GET', '/usage');
    }
}
