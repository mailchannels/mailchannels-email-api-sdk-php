<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /webhook-batch`. Spec schema:
 * `WebhookBatchResult`. Wraps a list of {@see WebhookBatch} entries
 * under the `webhook_batches` JSON key.
 */
final class WebhookBatchListResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<WebhookBatch>|null $webhookBatches
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $webhookBatches = null,
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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'webhook_batches');
        $batches = $rawList === null
            ? null
            : array_map(
                static fn (array $b): WebhookBatch => WebhookBatch::fromArray($b),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            webhookBatches: $batches,
        );
    }
}
