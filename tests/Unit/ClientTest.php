<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit;

use MailChannels\Client;
use MailChannels\Exception\AuthenticationException;
use MailChannels\Exception\ConfigurationException;
use MailChannels\Exception\InvalidRequestException;
use MailChannels\Exception\RateLimitException;
use MailChannels\Exception\ServerException;
use MailChannels\Tests\Support\Fixtures;
use MailChannels\Tests\Support\MockHttpClient;
use MailChannels\Version;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testFromConfigUsesSuppliedConfig(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['data' => []]));
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $config = new \MailChannels\Config(
            apiKey: 'mc-from-config',
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $http,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $client = Client::fromConfig($config);
        $this->assertSame($config, $client->config());
        $client->usage->retrieve();

        $req = $http->lastRequest();
        $this->assertSame('mc-from-config', $req->getHeaderLine('X-Api-Key'));
        $this->assertStringEndsWith('/tx/v1/usage', (string) $req->getUri());
    }

    public function testConstructorRejectsConfigMixedWithConvenienceOptions(): void
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $config = new \MailChannels\Config(
            apiKey: 'mc-from-config',
            httpClient: new MockHttpClient(),
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $this->expectException(ConfigurationException::class);
        new Client(apiKey: 'mc-other', config: $config);
    }

    public function testSendsApiKeyAndUserAgent(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['data' => []]));
        $client = Fixtures::client($http, apiKey: 'mc-test-123');

        $client->usage->retrieve();

        $req = $http->lastRequest();
        $this->assertSame('mc-test-123', $req->getHeaderLine('X-Api-Key'));
        $this->assertSame(Version::userAgent(), $req->getHeaderLine('User-Agent'));
        $this->assertSame('application/json', $req->getHeaderLine('Accept'));
        $this->assertSame('GET', $req->getMethod());
        $this->assertStringEndsWith('/tx/v1/usage', (string) $req->getUri());
    }

    public function testMissingApiKeyRaisesConfigurationException(): void
    {
        $http = new MockHttpClient();
        $client = new Client(
            apiKey: null,
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $http,
            requestFactory: new \Nyholm\Psr7\Factory\Psr17Factory(),
            streamFactory: new \Nyholm\Psr7\Factory\Psr17Factory(),
        );

        $this->expectException(ConfigurationException::class);
        $client->usage->retrieve();
    }

    public function testRaisesAuthenticationExceptionOn401(): void
    {
        $http = (new MockHttpClient())->queue(
            Fixtures::jsonResponse(401, ['message' => 'Invalid API key'])
        );
        $client = Fixtures::client($http);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');
        $client->usage->retrieve();
    }

    public function testRaisesRateLimitExceptionWithRetryAfter(): void
    {
        $http = (new MockHttpClient())->queue(
            Fixtures::jsonResponse(429, ['message' => 'Slow down'], ['Retry-After' => '12'])
        );
        $client = Fixtures::client($http);

        try {
            $client->usage->retrieve();
            $this->fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame('12', $e->getRetryAfter());
            $this->assertSame('rate_limit_error', $e->getErrorType());
        }
    }

    public function testRaisesServerExceptionOn500(): void
    {
        $http = (new MockHttpClient())->queue(
            Fixtures::jsonResponse(500, ['message' => 'Internal'])
        );
        $client = Fixtures::client($http);

        $this->expectException(ServerException::class);
        $client->usage->retrieve();
    }

    public function testRaisesInvalidRequestOnGeneric4xx(): void
    {
        $http = (new MockHttpClient())->queue(
            Fixtures::jsonResponse(422, ['message' => 'Bad payload'])
        );
        $client = Fixtures::client($http);

        $this->expectException(InvalidRequestException::class);
        $client->usage->retrieve();
    }

    public function testExceptionExposesRequestIdFromHeaders(): void
    {
        $http = (new MockHttpClient())->queue(
            Fixtures::jsonResponse(500, ['message' => 'oops'], ['X-Request-ID' => 'abc-123'])
        );
        $client = Fixtures::client($http);

        try {
            $client->usage->retrieve();
        } catch (ServerException $e) {
            $this->assertSame('abc-123', $e->getRequestId());
        }
    }
}
