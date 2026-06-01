<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /metrics/engagement`. Spec schema:
 * `MetricsEngagement`.
 *
 * Exposes top-level open/click counts plus their time-series breakdowns
 * under the `*Buckets` properties.
 */
final class MetricsEngagementResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<MetricsBucket>|null $openBuckets
     * @param list<MetricsBucket>|null $clickBuckets
     * @param list<MetricsBucket>|null $openTrackingDeliveredBuckets
     * @param list<MetricsBucket>|null $clickTrackingDeliveredBuckets
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?int $open = null,
        public readonly ?int $click = null,
        public readonly ?int $openTrackingDelivered = null,
        public readonly ?int $clickTrackingDelivered = null,
        public readonly ?array $openBuckets = null,
        public readonly ?array $clickBuckets = null,
        public readonly ?array $openTrackingDeliveredBuckets = null,
        public readonly ?array $clickTrackingDeliveredBuckets = null,
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
            open: ResponseValidator::asNullableInt($body, 'open'),
            click: ResponseValidator::asNullableInt($body, 'click'),
            openTrackingDelivered: ResponseValidator::asNullableInt($body, 'open_tracking_delivered'),
            clickTrackingDelivered: ResponseValidator::asNullableInt($body, 'click_tracking_delivered'),
            openBuckets: MetricsBucket::listFromParent($buckets, 'open'),
            clickBuckets: MetricsBucket::listFromParent($buckets, 'click'),
            openTrackingDeliveredBuckets: MetricsBucket::listFromParent($buckets, 'open_tracking_delivered'),
            clickTrackingDeliveredBuckets: MetricsBucket::listFromParent($buckets, 'click_tracking_delivered'),
        );
    }
}
