<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Internal\ResponseValidator;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Emails::queue()}
 * for the `/send-async` endpoint. The server queues the message and acks
 * with a request id plus a queued-at timestamp — there is **no** message
 * id at this point because delivery hasn't happened yet. The actual
 * per-message identifiers arrive later via webhook events.
 *
 * Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
 * helpers. `requestId()` is overridden to check the body's `request_id`
 * field first, falling back to header inspection.
 */
final class QueuedSendResponse extends Response
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
        public readonly ?string $queuedAt = null,
    ) {
        parent::__construct($data, $statusCode, $headers, $rawBody);
    }

    /**
     * Build a QueuedSendResponse from a generic {@see Response}, validating
     * field types as it goes.
     *
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->data;
        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            queuedAt: ResponseValidator::asNullableString($body, 'queued_at'),
        );
    }

    public function requestId(): ?string
    {
        $bodyValue = $this->data['request_id'] ?? null;
        if (is_string($bodyValue) && $bodyValue !== '') {
            return $bodyValue;
        }
        return parent::requestId();
    }
}
