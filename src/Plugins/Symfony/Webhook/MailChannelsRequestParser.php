<?php

namespace MailChannels\Plugins\Symfony\Webhook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;

class MailChannelsRequestParser extends AbstractRequestParser
{

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        // TODO: Implement getRequestMatcher() method.
    }

    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent|array|null
    {
        // TODO: Implement doParse() method.
    }
}
