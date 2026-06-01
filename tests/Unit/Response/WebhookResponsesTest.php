<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\WebhookBatch;
use MailChannels\Response\WebhookBatchDuration;
use MailChannels\Response\WebhookBatchListResponse;
use MailChannels\Response\WebhookEndpointResponse;
use MailChannels\Response\WebhookListResponse;
use MailChannels\Response\WebhookPublicKeyResponse;
use MailChannels\Response\WebhookResendResponse;
use MailChannels\Response\WebhookSubscription;
use MailChannels\Response\WebhookValidationResult;
use MailChannels\Response\WebhookValidationResultsResponse;
use PHPUnit\Framework\TestCase;

final class WebhookResponsesTest extends TestCase
{
    // ----- GET /webhook -----

    public function testListResponseExtractsTypedSubscriptions(): void
    {
        // The API returns a bare JSON array; Transport wraps it as
        // ['data' => [...]] in the decoded body.
        $body = [
            'data' => [
                ['webhook' => 'https://example.com/hook1'],
                ['webhook' => 'https://example.com/hook2'],
            ],
        ];

        $typed = WebhookListResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertNotNull($typed->webhooks);
        $this->assertCount(2, $typed->webhooks);
        $this->assertInstanceOf(WebhookSubscription::class, $typed->webhooks[0]);
        $this->assertSame('https://example.com/hook1', $typed->webhooks[0]->webhook);
        $this->assertSame('https://example.com/hook2', $typed->webhooks[1]->webhook);
    }

    public function testListResponseLeavesWebhooksNullWhenBodyEmpty(): void
    {
        $typed = WebhookListResponse::fromResponse(new Response(data: [], statusCode: 200));
        $this->assertNull($typed->webhooks);
    }

    public function testListResponseAcceptsEmptyArray(): void
    {
        $typed = WebhookListResponse::fromResponse(
            new Response(data: ['data' => []], statusCode: 200),
        );
        $this->assertSame([], $typed->webhooks);
    }

    // ----- GET /webhook-batch -----

    public function testBatchListResponseExtractsTypedBatches(): void
    {
        $body = [
            'webhook_batches' => [
                [
                    'batch_id' => 12345,
                    'customer_handle' => 'tenant42',
                    'webhook' => 'https://example.com/hook',
                    'status' => '2xx_response',
                    'status_code' => 200,
                    'event_count' => 25,
                    'created_at' => '2026-05-30T12:00:00Z',
                    'duration' => ['value' => 145, 'unit' => 'milliseconds'],
                ],
                [
                    'batch_id' => 12346,
                    'customer_handle' => 'tenant42',
                    'webhook' => 'https://example.com/hook',
                    'status' => 'no_response',
                    'status_code' => null,
                    'event_count' => 10,
                    'created_at' => '2026-05-30T12:01:00Z',
                ],
            ],
        ];

        $typed = WebhookBatchListResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertNotNull($typed->webhookBatches);
        $this->assertCount(2, $typed->webhookBatches);

        $first = $typed->webhookBatches[0];
        $this->assertInstanceOf(WebhookBatch::class, $first);
        $this->assertSame(12345, $first->batchId);
        $this->assertSame('tenant42', $first->customerHandle);
        $this->assertSame('2xx_response', $first->status);
        $this->assertSame(200, $first->statusCode);
        $this->assertSame(25, $first->eventCount);
        $this->assertInstanceOf(WebhookBatchDuration::class, $first->duration);
        $this->assertSame(145, $first->duration->value);
        $this->assertSame('milliseconds', $first->duration->unit);

        $second = $typed->webhookBatches[1];
        $this->assertSame('no_response', $second->status);
        $this->assertNull($second->statusCode);
        $this->assertNull($second->duration);
    }

    public function testBatchListResponseRejectsBatchesNotAList(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'webhook_batches' must be a list");
        WebhookBatchListResponse::fromResponse(new Response(
            data: ['webhook_batches' => ['batch_id' => 1]],
            statusCode: 200,
        ));
    }

    // ----- POST /webhook-batch/{batch_id}/resend -----

    public function testResendResponseExtractsFlatShape(): void
    {
        $body = [
            'batch_id' => 12345,
            'customer_handle' => 'tenant42',
            'webhook' => 'https://example.com/hook',
            'status_code' => 200,
            'event_count' => 25,
            'duration_in_ms' => 145,
            'created_at' => '2026-05-30T12:00:00Z',
        ];

        $typed = WebhookResendResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame(12345, $typed->batchId);
        $this->assertSame('tenant42', $typed->customerHandle);
        $this->assertSame(200, $typed->webhookStatusCode);
        $this->assertSame(145, $typed->durationInMs);
        $this->assertSame(25, $typed->eventCount);
        $this->assertSame('2026-05-30T12:00:00Z', $typed->createdAt);
    }

    public function testResendResponseDistinguishesSdkStatusCodeFromWebhookStatusCode(): void
    {
        // The SDK call returned 200 (response success); the receiving webhook
        // endpoint returned 500. Both should be visible, with no collision.
        $typed = WebhookResendResponse::fromResponse(new Response(
            data: ['batch_id' => 99, 'status_code' => 500],
            statusCode: 200,
        ));

        $this->assertSame(200, $typed->statusCode);             // inherited Response::$statusCode
        $this->assertSame(500, $typed->webhookStatusCode);      // body.status_code
    }

    // ----- GET /webhook/public-key -----

    public function testPublicKeyResponseExtractsKeyMaterial(): void
    {
        $body = ['id' => 'key-2026-01', 'key' => 'MCowBQYDK2VwAyEA...'];

        $typed = WebhookPublicKeyResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame('key-2026-01', $typed->id);
        $this->assertSame('MCowBQYDK2VwAyEA...', $typed->key);
    }

    // ----- POST /webhook/validate -----

    public function testValidationResultsResponseExtractsAllPassedAndPerWebhookResults(): void
    {
        $body = [
            'all_passed' => false,
            'results' => [
                [
                    'webhook' => 'https://example.com/hook1',
                    'result' => 'passed',
                    'response' => ['status' => 200, 'body' => 'ok'],
                ],
                [
                    'webhook' => 'https://example.com/hook2',
                    'result' => 'failed',
                    'response' => ['status' => 500, 'body' => 'internal error'],
                ],
            ],
        ];

        $typed = WebhookValidationResultsResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertFalse($typed->allPassed);
        $this->assertNotNull($typed->results);
        $this->assertCount(2, $typed->results);

        $first = $typed->results[0];
        $this->assertInstanceOf(WebhookValidationResult::class, $first);
        $this->assertSame('https://example.com/hook1', $first->webhook);
        $this->assertSame('passed', $first->result);
        $this->assertInstanceOf(WebhookEndpointResponse::class, $first->response);
        $this->assertSame(200, $first->response->status);
        $this->assertSame('ok', $first->response->body);

        $second = $typed->results[1];
        $this->assertSame('failed', $second->result);
        $this->assertNotNull($second->response);
        $this->assertSame(500, $second->response->status);
    }

    public function testValidationResultsRejectsWrongTypeAllPassed(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'all_passed' must be a bool");
        WebhookValidationResultsResponse::fromResponse(new Response(
            data: ['all_passed' => 'yes'],
            statusCode: 200,
        ));
    }

    // ----- HTTP metadata + JSON round-trips -----

    public function testHttpMetadataPreservedAcrossAllResponseClasses(): void
    {
        $raw = new Response(data: [], statusCode: 200, headers: ['X-Request-ID' => 'rid-wh']);

        $this->assertSame('rid-wh', WebhookListResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-wh', WebhookBatchListResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-wh', WebhookResendResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-wh', WebhookPublicKeyResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-wh', WebhookValidationResultsResponse::fromResponse($raw)->requestId());
    }

    public function testWebhookSubscriptionJsonSerialize(): void
    {
        $sub = new WebhookSubscription(webhook: 'https://example.com/hook');
        $this->assertSame(['webhook' => 'https://example.com/hook'], $sub->jsonSerialize());
    }

    public function testWebhookBatchDurationJsonSerialize(): void
    {
        $duration = new WebhookBatchDuration(value: 100, unit: 'milliseconds');
        $this->assertSame(['value' => 100, 'unit' => 'milliseconds'], $duration->jsonSerialize());
    }

    public function testWebhookEndpointResponseJsonSerializeOmitsNullFields(): void
    {
        $r = new WebhookEndpointResponse(status: 200);
        $this->assertSame(['status' => 200], $r->jsonSerialize());
    }
}
