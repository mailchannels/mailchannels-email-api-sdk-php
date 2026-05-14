<?php

declare(strict_types=1);

namespace MailChannels\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 client that records every outbound request and replies from a
 * scripted queue. Used so unit tests can assert exact wire-level behaviour
 * without standing up a real HTTP server.
 */
final class MockHttpClient implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responses = [];

    /** @var list<RequestInterface> */
    public array $sentRequests = [];

    public function queue(ResponseInterface $response): self
    {
        $this->responses[] = $response;
        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->sentRequests[] = $request;
        if ($this->responses === []) {
            throw new \LogicException('MockHttpClient has no queued response.');
        }
        return array_shift($this->responses);
    }

    public function lastRequest(): RequestInterface
    {
        if ($this->sentRequests === []) {
            throw new \LogicException('No requests have been sent.');
        }
        return $this->sentRequests[array_key_last($this->sentRequests)];
    }

    /** @return array<string, mixed> */
    public function lastJson(): array
    {
        $body = (string) $this->lastRequest()->getBody();
        if ($body === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \LogicException('Last request body is not JSON.');
        }
        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
