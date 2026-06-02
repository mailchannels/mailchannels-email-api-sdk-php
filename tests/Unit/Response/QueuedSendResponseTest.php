<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\QueuedSendResponse;
use PHPUnit\Framework\TestCase;

final class QueuedSendResponseTest extends TestCase
{
    public function testFromResponseExtractsTypedFields(): void
    {
        $response = new Response(
            data: [
                'request_id' => 'rid-1',
                'queued_at' => '2026-06-01T12:00:00Z',
            ],
            statusCode: 202,
        );

        $typed = new QueuedSendResponse($response);

        $this->assertSame('2026-06-01T12:00:00Z', $typed->queuedAt);
        $this->assertSame('rid-1', $typed->requestId());
    }

    public function testFromResponseLeavesFieldsNullWhenBodyEmpty(): void
    {
        $response = new Response(data: [], statusCode: 202);

        $typed = new QueuedSendResponse($response);

        $this->assertNull($typed->queuedAt);
    }

    public function testFromResponseRejectsWrongTypeQueuedAt(): void
    {
        $response = new Response(
            data: ['queued_at' => 1748812800],
            statusCode: 202,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'queued_at' must be a string");
        new QueuedSendResponse($response);
    }

    public function testPreservesHttpMetadata(): void
    {
        $response = new Response(
            data: ['queued_at' => '2026-06-01T12:00:00Z'],
            statusCode: 202,
            headers: ['X-Request-ID' => 'rid-from-header'],
            rawBody: '{"queued_at":"2026-06-01T12:00:00Z"}',
        );

        $typed = new QueuedSendResponse($response);

        $this->assertSame(202, $typed->statusCode);
        $this->assertSame('rid-from-header', $typed->requestId());
        $this->assertSame('{"queued_at":"2026-06-01T12:00:00Z"}', $typed->rawBody);
    }
}
