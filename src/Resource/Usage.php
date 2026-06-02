<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use MailChannels\Response\UsageResponse;

final class Usage extends AbstractResource
{
    public function retrieve(): UsageResponse
    {
        return new UsageResponse($this->request('GET', '/usage'));
    }
}
