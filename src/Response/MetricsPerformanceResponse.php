<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /metrics/performance`. Spec schema:
 * `MetricsPerformance`.
 *
 * Exposes top-level processed/delivered/bounced counts plus their
 * time-series breakdowns under the `*Buckets` properties.
 */
final class MetricsPerformanceResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<MetricsBucket>|null $processedBuckets
     * @param list<MetricsBucket>|null $deliveredBuckets
     * @param list<MetricsBucket>|null $bouncedBuckets
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?int $processed = null,
        public readonly ?int $delivered = null,
        public readonly ?int $bounced = null,
        public readonly ?array $processedBuckets = null,
        public readonly ?array $deliveredBuckets = null,
        public readonly ?array $bouncedBuckets = null,
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
        $buckets = ResponseValidator::asNullableAssocArray($body, 'buckets');

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            startTime: ResponseValidator::asNullableString($body, 'start_time'),
            endTime: ResponseValidator::asNullableString($body, 'end_time'),
            processed: ResponseValidator::asNullableInt($body, 'processed'),
            delivered: ResponseValidator::asNullableInt($body, 'delivered'),
            bounced: ResponseValidator::asNullableInt($body, 'bounced'),
            processedBuckets: MetricsBucket::listFromParent($buckets, 'processed'),
            deliveredBuckets: MetricsBucket::listFromParent($buckets, 'delivered'),
            bouncedBuckets: MetricsBucket::listFromParent($buckets, 'bounced'),
        );
    }
}
