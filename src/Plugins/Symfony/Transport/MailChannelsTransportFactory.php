<?php

namespace MailChannels\Plugins\Symfony\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Builds a {@see MailChannelsApiTransport} from a Symfony Mailer DSN, e.g.:
 *
 *   mailchannels+api://API_KEY@default
 *
 * To send via a sub-account, use that sub-account's own API key — i.e. a separate transport/DSN per sub-account.
 */
final class MailChannelsTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        if (!\in_array($dsn->getScheme(), $this->getSupportedSchemes(), true)) {
            throw new UnsupportedSchemeException($dsn, 'mailchannels', $this->getSupportedSchemes());
        }

        $apiKey = $this->getUser($dsn);
        $host = $dsn->getHost() !== 'default' ? $dsn->getHost() : null;
        $baseUrl = null;
        if ($host !== null) {
            $portString = '';
            if ($dsn->getPort() !== null) {
                $portString = ':'.$dsn->getPort();
            }
            $baseUrl = sprintf('https://%s%s', $host, $portString);
        }

        $dispatcher = $this->dispatcher instanceof EventDispatcherInterface ? $this->dispatcher : null;

        $logger = $this->logger instanceof LoggerInterface ? $this->logger : null;

        $httpClient = null;
        if ($this->client instanceof HttpClientInterface && class_exists(Psr18Client::class)) {
            $httpClient = new Psr18Client($this->client);
        }

        return new MailChannelsApiTransport(
            $apiKey,
            $baseUrl,
            httpClient: $httpClient,
            requestFactory: $httpClient,
            streamFactory: $httpClient,
            dispatcher: $dispatcher,
            logger: $logger,
        );
    }

    /** @return string[] */
    protected function getSupportedSchemes(): array
    {
        return ['mailchannels', 'mailchannels+api'];
    }
}
