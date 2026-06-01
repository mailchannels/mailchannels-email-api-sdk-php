<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /metrics/senders/{sender_type}`. Spec schema:
 * `MetricsSenderResponse`.
 *
 * Differs from the time-series metrics endpoints: this one is a
 * paginated list of per-sender rollups (`$senders`), not a bucketed
 * time series. `$limit`/`$offset`/`$total` describe the pagination
 * window.
 */
final class MetricsSenderResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<MetricsSender>|null $senders
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
        public readonly ?int $total = null,
        public readonly ?array $senders = null,
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
        $rawSenders = ResponseValidator::asNullableListOfArrays($body, 'senders');
        $senders = $rawSenders === null
            ? null
            : array_map(
                static fn (array $s): MetricsSender => MetricsSender::fromArray($s),
                $rawSenders,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            startTime: ResponseValidator::asNullableString($body, 'start_time'),
            endTime: ResponseValidator::asNullableString($body, 'end_time'),
            limit: ResponseValidator::asNullableInt($body, 'limit'),
            offset: ResponseValidator::asNullableInt($body, 'offset'),
            total: ResponseValidator::asNullableInt($body, 'total'),
            senders: $senders,
        );
    }
}
