<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response\SendResult;
use PHPUnit\Framework\TestCase;

final class SendResultTest extends TestCase
{
    public function testFromArrayExtractsAllSpecFields(): void
    {
        $result = SendResult::fromArray([
            'index' => 0,
            'message_id' => 'mid-abc',
            'reason' => 'Accepted for delivery',
            'status' => 'sent',
        ]);

        $this->assertSame(0, $result->index);
        $this->assertSame('mid-abc', $result->messageId);
        $this->assertSame('Accepted for delivery', $result->reason);
        $this->assertSame('sent', $result->status);
    }

    public function testFromArrayWithEmptyBodyLeavesAllFieldsNull(): void
    {
        $result = SendResult::fromArray([]);

        $this->assertNull($result->index);
        $this->assertNull($result->messageId);
        $this->assertNull($result->reason);
        $this->assertNull($result->status);
    }

    public function testFromArrayRejectsIndexNotAnInt(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'index' must be an int");
        SendResult::fromArray(['index' => '0']);
    }

    public function testFromArrayRejectsMessageIdNotAString(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'message_id' must be a string");
        SendResult::fromArray(['message_id' => ['not', 'a', 'string']]);
    }

    public function testFromArrayRejectsStatusNotAString(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'status' must be a string");
        SendResult::fromArray(['status' => 42]);
    }

    public function testJsonSerializeOmitsNullFields(): void
    {
        $result = new SendResult(messageId: 'mid-1', status: 'sent');

        $this->assertSame(
            ['message_id' => 'mid-1', 'status' => 'sent'],
            $result->jsonSerialize(),
        );
    }

    public function testJsonSerializeRoundTripWithAllFields(): void
    {
        $original = [
            'index' => 1,
            'message_id' => 'mid-xyz',
            'reason' => 'ok',
            'status' => 'sent',
        ];

        $serialized = SendResult::fromArray($original)->jsonSerialize();

        $this->assertSame($original, $serialized);
    }

    public function testFailedStatusIsAcceptedVerbatim(): void
    {
        $result = SendResult::fromArray([
            'index' => 2,
            'reason' => 'Permanent failure: recipient mailbox unavailable',
            'status' => 'failed',
        ]);

        $this->assertSame('failed', $result->status);
        $this->assertNull($result->messageId);
    }
}
