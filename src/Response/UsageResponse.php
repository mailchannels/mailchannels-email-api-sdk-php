<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Internal\ResponseValidator;
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
    public readonly ?int $totalUsage;
    public readonly ?string $periodStartDate;
    public readonly ?string $periodEndDate;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public function __construct(Response $response)
    {
        $body = $response->data;
        parent::__construct(
            $response->data,
            $response->statusCode,
            $response->headers,
            $response->rawBody,
        );
        $this->totalUsage = ResponseValidator::asNullableInt($body, 'total_usage');
        $this->periodStartDate = ResponseValidator::asNullableString($body, 'period_start_date');
        $this->periodEndDate = ResponseValidator::asNullableString($body, 'period_end_date');
    }
}
