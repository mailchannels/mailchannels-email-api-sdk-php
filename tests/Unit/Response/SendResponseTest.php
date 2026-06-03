<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\SendResponse;
use MailChannels\Response\SendResult;
use PHPUnit\Framework\TestCase;

final class SendResponseTest extends TestCase
{
    public function testNormalSendBodyExtractsTypedResults(): void
    {
        $response = new Response(
            data: [
                'request_id' => 'rid-1',
                'results' => [
                    ['index' => 0, 'message_id' => 'mid-a', 'status' => 'sent'],
                    ['index' => 1, 'message_id' => 'mid-b', 'status' => 'sent'],
                ],
            ],
            statusCode: 202,
            headers: ['X-Request-ID' => 'header-rid'],
        );

        $typed = new SendResponse($response);

        $this->assertNotNull($typed->results);
        $this->assertCount(2, $typed->results);
        $this->assertContainsOnlyInstancesOf(SendResult::class, $typed->results);
        $this->assertSame('mid-a', $typed->results[0]->messageId);
        $this->assertSame(0, $typed->results[0]->index);
        $this->assertSame('sent', $typed->results[0]->status);
        $this->assertSame('mid-b', $typed->results[1]->messageId);
        $this->assertNull($typed->dryRunData);
    }

    public function testDryRunBodyExtractsListOfRenderedMessages(): void
    {
        $response = new Response(
            data: [
                'data' => [
                    "From: sender@example.com\r\nTo: a@example.com\r\nSubject: hi\r\n\r\nbody A",
                    "From: sender@example.com\r\nTo: b@example.com\r\nSubject: hi\r\n\r\nbody B",
                ],
            ],
            statusCode: 200,
        );

        $typed = new SendResponse($response);

        $this->assertNotNull($typed->dryRunData);
        $this->assertCount(2, $typed->dryRunData);
        $this->assertStringStartsWith('From: sender@example.com', $typed->dryRunData[0]);
        $this->assertNull($typed->results);
    }

    public function testEmptyBodyLeavesAllTypedFieldsNull(): void
    {
        $response = new Response(data: [], statusCode: 202);

        $typed = new SendResponse($response);

        $this->assertNull($typed->results);
        $this->assertNull($typed->dryRunData);
    }

    public function testRejectsResultsThatAreNotAList(): void
    {
        $response = new Response(
            data: ['results' => ['unexpected_key' => 'value']],
            statusCode: 202,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'results' must be a list");
        new SendResponse($response);
    }

    public function testRejectsResultEntryWithWrongTypeIndex(): void
    {
        $response = new Response(
            data: ['results' => [['index' => 'zero', 'message_id' => 'mid-a']]],
            statusCode: 202,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'index' must be an int");
        new SendResponse($response);
    }

    public function testRejectsDryRunDataEntryThatIsNotAString(): void
    {
        $response = new Response(
            data: ['data' => ['rendered string', 42]],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'data'[1] must be a string");
        new SendResponse($response);
    }

    public function testPreservesHttpMetadataFromOriginalResponse(): void
    {
        $response = new Response(
            data: ['request_id' => 'rid-2'],
            statusCode: 202,
            headers: ['Content-Type' => 'application/json', 'X-Request-ID' => 'header-rid'],
            rawBody: '{"request_id":"rid-2"}',
        );

        $typed = new SendResponse($response);

        $this->assertSame(202, $typed->statusCode);
        $this->assertSame('application/json', $typed->headers['Content-Type']);
        $this->assertSame('{"request_id":"rid-2"}', $typed->rawBody);
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
            data: ['results' => []],
            statusCode: 202,
            headers: ['X-Request-ID' => 'header-rid'],
        );

        $typed = new SendResponse($response);

        $this->assertSame('header-rid', $typed->requestId());
    }

    public function testArrayAccessKeepsWorkingOnTypedResponse(): void
    {
        $response = new Response(
            data: [
                'request_id' => 'rid-x',
                'extra_unmodeled_field' => 'kept',
                'results' => [['index' => 0, 'message_id' => 'mid-x', 'status' => 'sent']],
            ],
            statusCode: 202,
        );

        $typed = new SendResponse($response);

        // Typed property access for the modeled list.
        $this->assertNotNull($typed->results);
        $this->assertSame('mid-x', $typed->results[0]->messageId);
        // Inherited array-access surface continues to work.
        $this->assertSame('rid-x', $typed['request_id']);
        $this->assertSame('kept', $typed['extra_unmodeled_field']);
        $this->assertSame('rid-x', $typed->get('request_id'));
    }
}
