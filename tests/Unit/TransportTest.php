<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit;

use DateTimeImmutable;
use DateTimeInterface;
use MailChannels\Client;
use MailChannels\Enum\MetricsInterval;
use MailChannels\Enum\MetricsSenderType;
use MailChannels\Enum\MetricsSortOrder;
use MailChannels\Exception\ApiException;
use MailChannels\Exception\BadGatewayException;
use MailChannels\Exception\ConflictException;
use MailChannels\Exception\ForbiddenException;
use MailChannels\Exception\NotFoundException;
use MailChannels\Exception\PayloadTooLargeException;
use MailChannels\Exception\ServerException;
use MailChannels\Exception\TransportException;
use MailChannels\Tests\Support\Fixtures;
use MailChannels\Tests\Support\MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Transport-level behavior: HTTP status → exception mapping, transport error
 * translation, response decoding edge cases, and query-string serialization
 * across the supported scalar/enum/date types.
 *
 * ClientTest already covers the happy-path API key + user-agent wiring and a
 * subset of status code mappings (401, 422, 429, 500). This file fills in the
 * remaining branches in MailChannels\Transport.
 */
final class TransportTest extends TestCase
{
    /** @return array<string, array{int, class-string<\Throwable>}> */
    public static function statusCodeProvider(): array
    {
        return [
            '403 Forbidden' => [403, ForbiddenException::class],
            '404 Not Found' => [404, NotFoundException::class],
            '409 Conflict' => [409, ConflictException::class],
            '413 Payload Too Large' => [413, PayloadTooLargeException::class],
            '502 Bad Gateway' => [502, BadGatewayException::class],
            '503 Service Unavailable' => [503, ServerException::class],
        ];
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @dataProvider statusCodeProvider
     */
    public function testMapsHttpStatusToException(int $status, string $exceptionClass): void
    {
        $http = (new MockHttpClient())->queue(
            Fixtures::jsonResponse($status, ['message' => 'error'])
        );
        $client = Fixtures::client($http);

        $this->expectException($exceptionClass);
        $client->usage->retrieve();
    }

    public function testUnmappedNon2xxStatusRaisesApiException(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(304, null));
        $client = Fixtures::client($http);

        $this->expectException(ApiException::class);
        $client->usage->retrieve();
    }

    public function testPsr18ClientExceptionBecomesTransportException(): void
    {
        $clientException = new class('boom') extends \RuntimeException implements ClientExceptionInterface {};
        $throwingHttp = new class($clientException) implements ClientInterface {
            public function __construct(private readonly \Throwable $e)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw $this->e;
            }
        };
        $factory = new Psr17Factory();
        $client = new Client(
            apiKey: 'mc-test',
            baseUrl: 'https://api.example.test/tx/v1',
            httpClient: $throwingHttp,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        try {
            $client->usage->retrieve();
            $this->fail('Expected TransportException.');
        } catch (TransportException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
            $this->assertSame($clientException, $e->getPrevious());
        }
    }

    /** @return array<string, array{array<string, string>, string}> */
    public static function errorMessageKeyProvider(): array
    {
        return [
            'error key' => [['error' => 'thing broke'], 'thing broke'],
            'detail key' => [['detail' => 'detail explanation'], 'detail explanation'],
            'title key' => [['title' => 'short title'], 'short title'],
        ];
    }

    /**
     * @param array<string, string> $body
     * @dataProvider errorMessageKeyProvider
     */
    public function testExceptionMessageFallsBackToAlternativeKeys(array $body, string $expected): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(500, $body));
        $client = Fixtures::client($http);

        try {
            $client->usage->retrieve();
            $this->fail('Expected ServerException.');
        } catch (ServerException $e) {
            $this->assertSame($expected, $e->getMessage());
        }
    }

    public function testExceptionMessageFallsBackToRawBodyWhenNoKnownKeysPresent(): void
    {
        $factory = new Psr17Factory();
        $response = $factory->createResponse(500)
            ->withBody($factory->createStream('something exploded on the server side'));
        $http = (new MockHttpClient())->queue($response);
        $client = Fixtures::client($http);

        try {
            $client->usage->retrieve();
            $this->fail('Expected ServerException.');
        } catch (ServerException $e) {
            $this->assertSame('something exploded on the server side', $e->getMessage());
        }
    }

    public function testExceptionMessageDefaultsToStatusBasedStringForEmptyBody(): void
    {
        $factory = new Psr17Factory();
        $http = (new MockHttpClient())->queue($factory->createResponse(500));
        $client = Fixtures::client($http);

        try {
            $client->usage->retrieve();
            $this->fail('Expected ServerException.');
        } catch (ServerException $e) {
            $this->assertStringContainsString('500', $e->getMessage());
        }
    }

    public function testEmptySuccessfulResponseBodyDecodesToEmptyArray(): void
    {
        $factory = new Psr17Factory();
        $http = (new MockHttpClient())->queue($factory->createResponse(204));
        $client = Fixtures::client($http);

        $response = $client->usage->retrieve();

        $this->assertSame([], $response->toArray());
    }

    public function testListResponseBodyIsWrappedUnderDataKey(): void
    {
        $factory = new Psr17Factory();
        $response = $factory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream('["a","b","c"]'));
        $http = (new MockHttpClient())->queue($response);
        $client = Fixtures::client($http);

        $payload = $client->usage->retrieve()->toArray();

        $this->assertSame(['a', 'b', 'c'], $payload['data']);
    }

    public function testQuerySerializesBackedEnumValueAndDateTimeAtomFormat(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['data' => []]));
        $client = Fixtures::client($http);
        $start = new DateTimeImmutable('2026-01-15T08:30:00+00:00');

        $client->metrics->engagement(
            startTime: $start,
            interval: MetricsInterval::Day,
        );

        $query = $http->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('interval=day', $query);
        $this->assertStringContainsString(
            'start_time=' . rawurlencode($start->format(DateTimeInterface::ATOM)),
            $query,
        );
    }

    public function testQuerySerializesIntegerValues(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['data' => []]));
        $client = Fixtures::client($http);

        $client->metrics->senders(
            senderType: MetricsSenderType::Campaigns,
            limit: 25,
            offset: 50,
            sortOrder: MetricsSortOrder::Descending,
        );

        $query = $http->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('limit=25', $query);
        $this->assertStringContainsString('offset=50', $query);
        $this->assertStringContainsString('sort_order=desc', $query);
    }
}
