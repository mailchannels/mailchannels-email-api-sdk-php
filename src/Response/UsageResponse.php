<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Usage::retrieve()}.
 *
 * `$totalUsage` is the count of accepted send-requests for the current
 * billing period; `$periodStartDate` / `$periodEndDate` bracket the period
 * the count covers. All fields are nullable — missing fields stay null so
 * the SDK stays forward-compatible with future API additions.
 *
 * Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
 * helpers.
 */
final class UsageResponse extends Response
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
        public readonly ?int $totalUsage = null,
        public readonly ?string $periodStartDate = null,
        public readonly ?string $periodEndDate = null,
    ) {
        parent::__construct($data, $statusCode, $headers, $rawBody);
    }

    /**
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
            totalUsage: ResponseValidator::asNullableInt($body, 'total_usage'),
            periodStartDate: ResponseValidator::asNullableString($body, 'period_start_date'),
            periodEndDate: ResponseValidator::asNullableString($body, 'period_end_date'),
        );
    }
}
