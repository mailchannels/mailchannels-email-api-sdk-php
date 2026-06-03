<?php

declare(strict_types=1);

namespace MailChannels\Tests\Integration;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory as GuzzleHttpFactory;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use MailChannels\Client;
use MailChannels\Exception\AuthenticationException;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end smoke test using Guzzle as the PSR-18 client and PSR-17 factory.
 * The MockHandler intercepts traffic so no network call is made, but every
 * other layer (PSR-7 messages, Guzzle's PSR-18 implementation, our Transport)
 * runs exactly as it would in production.
 */
final class GuzzleClientTest extends TestCase
{
    public function testSendThroughRealGuzzleStack(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(
                202,
                ['X-Request-ID' => 'guz-1'],
                json_encode([
                    'results' => [['index' => 0, 'message_id' => 'mid-99', 'status' => 'sent']],
                ]) ?: '',
            ),
        ]);
        $factory = new GuzzleHttpFactory();
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $client = new Client(
            apiKey: 'mc-guzzle',
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $guzzle,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $response = $client->emails->send([
            'from' => 'sender@example.com',
            'to' => 'recipient@example.net',
            'subject' => 'guzzle',
            'text' => 'body',
        ]);

        $this->assertSame(202, $response->statusCode);
        $this->assertNotNull($response->results);
        $this->assertSame('mid-99', $response->results[0]->messageId);
        $this->assertSame('guz-1', $response->requestId());

        $lastRequest = $mock->getLastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertSame('mc-guzzle', $lastRequest->getHeaderLine('X-Api-Key'));
        $this->assertStringEndsWith('/tx/v1/send', (string) $lastRequest->getUri());
    }

    public function testAuthFailureThroughGuzzleSurfacesTypedException(): void
    {
        $mock = new MockHandler([
            new GuzzleResponse(401, [], json_encode(['message' => 'Invalid API key']) ?: ''),
        ]);
        $factory = new GuzzleHttpFactory();
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock)]);

        $client = new Client(
            apiKey: 'wrong',
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $guzzle,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $this->expectException(AuthenticationException::class);
        $client->usage->retrieve();
    }
}
