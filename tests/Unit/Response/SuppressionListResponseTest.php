<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\SuppressionEntry;
use MailChannels\Response\SuppressionListResponse;
use PHPUnit\Framework\TestCase;

final class SuppressionListResponseTest extends TestCase
{
    public function testFromResponseExtractsTypedEntries(): void
    {
        $response = new Response(
            data: [
                'suppression_list' => [
                    [
                        'recipient' => 'a@example.com',
                        'suppression_types' => ['transactional', 'non-transactional'],
                        'notes' => 'manual opt-out',
                        'source' => 'api',
                        'sender' => 'sender@example.net',
                        'created_at' => '2026-05-30T12:00:00Z',
                    ],
                    [
                        'recipient' => 'b@example.com',
                        'source' => 'hard_bounce',
                    ],
                ],
            ],
            statusCode: 200,
        );

        $typed = SuppressionListResponse::fromResponse($response);

        $this->assertNotNull($typed->suppressionList);
        $this->assertCount(2, $typed->suppressionList);

        $first = $typed->suppressionList[0];
        $this->assertInstanceOf(SuppressionEntry::class, $first);
        $this->assertSame('a@example.com', $first->recipient);
        $this->assertSame(['transactional', 'non-transactional'], $first->suppressionTypes);
        $this->assertSame('manual opt-out', $first->notes);
        $this->assertSame('api', $first->source);
        $this->assertSame('sender@example.net', $first->sender);
        $this->assertSame('2026-05-30T12:00:00Z', $first->createdAt);

        $second = $typed->suppressionList[1];
        $this->assertSame('b@example.com', $second->recipient);
        $this->assertNull($second->notes);
        $this->assertNull($second->suppressionTypes);
        $this->assertSame('hard_bounce', $second->source);
    }

    public function testFromResponseLeavesListNullWhenBodyEmpty(): void
    {
        $typed = SuppressionListResponse::fromResponse(new Response(data: [], statusCode: 200));

        $this->assertNull($typed->suppressionList);
    }

    public function testFromResponseAcceptsEmptyList(): void
    {
        $typed = SuppressionListResponse::fromResponse(
            new Response(data: ['suppression_list' => []], statusCode: 200),
        );

        $this->assertSame([], $typed->suppressionList);
    }

    public function testFromResponseRejectsListThatIsNotAList(): void
    {
        $response = new Response(
            data: ['suppression_list' => ['recipient' => 'oops@example.com']],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'suppression_list' must be a list");
        SuppressionListResponse::fromResponse($response);
    }

    public function testFromResponseRejectsEntryWithWrongTypeRecipient(): void
    {
        $response = new Response(
            data: [
                'suppression_list' => [
                    ['recipient' => 12345],
                ],
            ],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'recipient' must be a string");
        SuppressionListResponse::fromResponse($response);
    }

    public function testFromResponseRejectsEntryWithWrongTypeSuppressionTypes(): void
    {
        $response = new Response(
            data: [
                'suppression_list' => [
                    [
                        'recipient' => 'a@example.com',
                        'suppression_types' => 'transactional',  // not a list
                    ],
                ],
            ],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'suppression_types' must be a list");
        SuppressionListResponse::fromResponse($response);
    }

    public function testPreservesHttpMetadata(): void
    {
        $response = new Response(
            data: ['suppression_list' => []],
            statusCode: 200,
            headers: ['X-Request-ID' => 'rid-supp', 'Content-Type' => 'application/json'],
            rawBody: '{"suppression_list":[]}',
        );

        $typed = SuppressionListResponse::fromResponse($response);

        $this->assertSame(200, $typed->statusCode);
        $this->assertSame('rid-supp', $typed->requestId());
        $this->assertSame('{"suppression_list":[]}', $typed->rawBody);
    }

    public function testSuppressionEntryJsonSerializeOmitsNullFields(): void
    {
        $entry = new SuppressionEntry(
            recipient: 'a@example.com',
            source: 'api',
        );

        $this->assertSame(
            ['recipient' => 'a@example.com', 'source' => 'api'],
            $entry->jsonSerialize(),
        );
    }
}
