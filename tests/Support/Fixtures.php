<?php

declare(strict_types=1);

namespace MailChannels\Tests\Support;

use MailChannels\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

final class Fixtures
{
    public static function client(MockHttpClient $http, string $apiKey = 'mc-test'): Client
    {
        $factory = new Psr17Factory();
        return new Client(
            apiKey: $apiKey,
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $http,
            requestFactory: $factory,
            streamFactory: $factory,
        );
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string>     $headers
     */
    public static function jsonResponse(int $status, ?array $body, array $headers = []): Response
    {
        $factory = new Psr17Factory();
        $payload = $body === null ? '' : (json_encode($body, JSON_UNESCAPED_SLASHES) ?: '');
        $stream = $factory->createStream($payload);
        $resolvedHeaders = array_merge(['Content-Type' => 'application/json'], $headers);
        return new Response($status, $resolvedHeaders, $stream);
    }
}
