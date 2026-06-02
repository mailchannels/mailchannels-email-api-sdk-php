<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\SendResponse;
use PHPUnit\Framework\TestCase;

final class SendResponseTest extends TestCase
{
    public function testFromResponseExtractsTypedFieldsFromNormalSendBody(): void
    {
        $response = new Response(
            data: [
                'message_id' => 'mid-1',
                'request_id' => 'rid-1',
                'results' => [
                    ['recipient' => 'a@example.com', 'status' => 'queued'],
                    ['recipient' => 'b@example.com', 'status' => 'queued'],
                ],
            ],
            statusCode: 202,
            headers: ['X-Request-ID' => 'header-rid'],
        );

        $typed = new SendResponse($response);

        $this->assertSame('mid-1', $typed->messageId);
        $this->assertNotNull($typed->results);
        $this->assertCount(2, $typed->results);
        $this->assertSame('a@example.com', $typed->results[0]['recipient']);
        $this->assertNull($typed->dryRunData);
    }

    public function testFromResponseExtractsDryRunData(): void
    {
        $response = new Response(
            data: [
                'data' => [
                    ['from' => 'sender@example.com', 'rendered' => '...'],
                ],
            ],
            statusCode: 200,
        );

        $typed = new SendResponse($response);

        $this->assertNotNull($typed->dryRunData);
        $this->assertCount(1, $typed->dryRunData);
        $this->assertNull($typed->messageId);
        $this->assertNull($typed->results);
    }

    public function testFromResponseLeavesAllTypedFieldsNullWhenBodyEmpty(): void
    {
        $response = new Response(data: [], statusCode: 202);

        $typed = new SendResponse($response);

        $this->assertNull($typed->messageId);
        $this->assertNull($typed->results);
        $this->assertNull($typed->dryRunData);
    }

    public function testFromResponseRejectsWrongTypeMessageId(): void
    {
        $response = new Response(
            data: ['message_id' => ['not', 'a', 'string']],
            statusCode: 202,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'message_id' must be a string");
        new SendResponse($response);
    }

    public function testFromResponseRejectsResultsThatAreNotAList(): void
    {
        $response = new Response(
            data: ['results' => ['unexpected_key' => 'value']],
            statusCode: 202,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'results' must be a list");
        new SendResponse($response);
    }

    public function testFromResponseRejectsResultsEntryThatIsNotAssoc(): void
    {
        $response = new Response(
            data: ['results' => ['plain-string-entry']],
            statusCode: 202,
        );

        $this->expectException(ResponseValidationException::class);
        new SendResponse($response);
    }

    public function testPreservesHttpMetadataFromOriginalResponse(): void
    {
        $response = new Response(
            data: ['message_id' => 'mid-2'],
            statusCode: 202,
            headers: ['Content-Type' => 'application/json', 'X-Request-ID' => 'rid-2'],
            rawBody: '{"message_id":"mid-2"}',
        );

        $typed = new SendResponse($response);

        $this->assertSame(202, $typed->statusCode);
        $this->assertSame('application/json', $typed->headers['Content-Type']);
        $this->assertSame('{"message_id":"mid-2"}', $typed->rawBody);
    }

    public function testRequestIdPrefersBodyOverHeaders(): void
    {
        $response = new Response(
            data: ['request_id' => 'body-rid'],
            statusCode: 202,
            headers: ['X-Request-ID' => 'header-rid'],
        );

        $typed = new SendResponse($response);

        $this->assertSame('body-rid', $typed->requestId());
    }

    public function testRequestIdFallsBackToHeadersWhenBodyMissing(): void
    {
        $response = new Response(
            data: ['message_id' => 'mid-3'],
            statusCode: 202,
            headers: ['X-Request-ID' => 'header-rid'],
        );

        $typed = new SendResponse($response);

        $this->assertSame('header-rid', $typed->requestId());
    }

    public function testArrayAccessKeepsWorkingOnTypedResponse(): void
    {
        $response = new Response(
            data: ['message_id' => 'mid-4', 'extra_unmodeled_field' => 'kept'],
            statusCode: 202,
        );

        $typed = new SendResponse($response);

        // Both typed property access and the inherited array-access surface
        // continue to work.
        $this->assertSame('mid-4', $typed->messageId);
        $this->assertSame('mid-4', $typed['message_id']);
        $this->assertSame('mid-4', $typed->get('message_id'));
        $this->assertSame('kept', $typed['extra_unmodeled_field']);
    }
}
