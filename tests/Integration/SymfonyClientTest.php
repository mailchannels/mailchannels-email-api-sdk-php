<?php

declare(strict_types=1);

namespace MailChannels\Tests\Integration;

use MailChannels\Client;
use MailChannels\Exception\InvalidRequestException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * End-to-end smoke test against Symfony's PSR-18 client. The MockHttpClient
 * intercepts traffic so no network call is made, but the Symfony adapter,
 * Nyholm PSR-7, our Transport, and resource code all run for real.
 */
final class SymfonyClientTest extends TestCase
{
    public function testSendThroughSymfonyHttpClient(): void
    {
        $responses = [
            new MockResponse(
                json_encode([
                    'request_id' => 'sym-rid',
                    'results' => [['index' => 0, 'message_id' => 'sym-1', 'status' => 'sent']],
                ]) ?: '',
                ['response_headers' => ['Content-Type' => 'application/json'], 'http_code' => 202],
            ),
        ];
        $symfony = new MockHttpClient($responses);
        $psr18 = new Psr18Client($symfony);
        $psr17 = new Psr17Factory();

        $client = new Client(
            apiKey: 'mc-symfony',
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $psr18,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );

        $response = $client->emails->send([
            'from' => 'sender@example.com',
            'to' => 'recipient@example.net',
            'subject' => 'symfony',
            'text' => 'body',
        ]);

        $this->assertSame(202, $response->statusCode);
        $this->assertNotNull($response->results);
        $this->assertSame('sym-1', $response->results[0]->messageId);
        $this->assertSame('sym-rid', $response->requestId());
    }

    public function testFailureThroughSymfonySurfacesTypedException(): void
    {
        $responses = [
            new MockResponse(
                json_encode(['message' => 'bad request']) ?: '',
                ['response_headers' => ['Content-Type' => 'application/json'], 'http_code' => 422],
            ),
        ];
        $symfony = new MockHttpClient($responses);
        $psr18 = new Psr18Client($symfony);
        $psr17 = new Psr17Factory();

        $client = new Client(
            apiKey: 'mc-symfony',
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $psr18,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );

        $this->expectException(InvalidRequestException::class);
        $client->usage->retrieve();
    }
}
