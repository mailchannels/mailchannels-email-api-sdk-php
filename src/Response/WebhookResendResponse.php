<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `POST /webhook-batch/{batch_id}/resend`. Spec
 * schema: `WebhookResendResponse`.
 *
 * Similar in shape to {@see WebhookBatch} but flatter: duration is
 * inlined as `$durationInMs` (nullable int when no response was
 * received) instead of a nested object, and there's no top-level
 * `status` enum.
 *
 * Note: the body field `status_code` (the HTTP status returned by the
 * customer's webhook endpoint during the resend) is exposed as
 * `$webhookStatusCode` rather than `$statusCode` to avoid shadowing
 * the inherited {@see Response::$statusCode} (which is MailChannels'
 * own HTTP response status for this SDK call).
 */
final class WebhookResendResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?int $batchId = null,
        public readonly ?string $customerHandle = null,
        public readonly ?string $webhook = null,
        public readonly ?int $webhookStatusCode = null,
        public readonly ?int $eventCount = null,
        public readonly ?int $durationInMs = null,
        public readonly ?string $createdAt = null,
    ) {
        parent::__construct($data, $statusCode, $headers, $rawBody);
    }

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->data;
        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            batchId: ResponseValidator::asNullableInt($body, 'batch_id'),
            customerHandle: ResponseValidator::asNullableString($body, 'customer_handle'),
            webhook: ResponseValidator::asNullableString($body, 'webhook'),
            webhookStatusCode: ResponseValidator::asNullableInt($body, 'status_code'),
            eventCount: ResponseValidator::asNullableInt($body, 'event_count'),
            durationInMs: ResponseValidator::asNullableInt($body, 'duration_in_ms'),
            createdAt: ResponseValidator::asNullableString($body, 'created_at'),
        );
    }
}
