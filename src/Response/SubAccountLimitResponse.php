<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for both `GET /sub-account/{handle}/limit` (returns
 * `Limit`: `{"sends": int}`) and `PUT /sub-account/{handle}/limit`
 * (returns `LimitUpdateResult`: `{"limit": {"sends": int}}`).
 *
 * The two endpoints have semantically equivalent payloads but
 * structurally different shapes — GET returns the limit fields at the
 * top level, PUT wraps them under a `limit` key. `fromResponse()`
 * unwraps the PUT shape transparently so callers see the same typed
 * `$sends` property in both cases.
 */
final class SubAccountLimitResponse extends Response
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
        public readonly ?int $sends = null,
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
        // The PUT response wraps the limit fields under a `limit` key
        // (LimitUpdateResult schema). The GET response inlines them
        // directly (Limit schema). Unwrap transparently.
        $limitData = ResponseValidator::asNullableAssocArray($body, 'limit') ?? $body;

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            sends: ResponseValidator::asNullableInt($limitData, 'sends'),
        );
    }
}
