<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Internal\ResponseValidator;
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
    public readonly ?string $startTime;
    public readonly ?string $endTime;
    public readonly ?int $open;
    public readonly ?int $click;
    public readonly ?int $openTrackingDelivered;
    public readonly ?int $clickTrackingDelivered;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $openBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $clickBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $openTrackingDeliveredBuckets;

    /** @var list<MetricsBucket>|null */
    public readonly ?array $clickTrackingDeliveredBuckets;

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
        $buckets = ResponseValidator::asNullableAssocArray($body, 'buckets');
        $this->startTime = ResponseValidator::asNullableString($body, 'start_time');
        $this->endTime = ResponseValidator::asNullableString($body, 'end_time');
        $this->open = ResponseValidator::asNullableInt($body, 'open');
        $this->click = ResponseValidator::asNullableInt($body, 'click');
        $this->openTrackingDelivered = ResponseValidator::asNullableInt($body, 'open_tracking_delivered');
        $this->clickTrackingDelivered = ResponseValidator::asNullableInt($body, 'click_tracking_delivered');
        $this->openBuckets = MetricsBucket::listFromParent($buckets, 'open');
        $this->clickBuckets = MetricsBucket::listFromParent($buckets, 'click');
        $this->openTrackingDeliveredBuckets = MetricsBucket::listFromParent($buckets, 'open_tracking_delivered');
        $this->clickTrackingDeliveredBuckets = MetricsBucket::listFromParent($buckets, 'click_tracking_delivered');
    }
}
