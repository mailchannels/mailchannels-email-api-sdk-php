<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /metrics/recipient-behaviour`. Spec schema:
 * `MetricsRecipientBehaviour`.
 *
 * Exposes top-level unsubscribe counts plus their time-series
 * breakdowns under the `*Buckets` properties.
 */
final class MetricsRecipientBehaviourResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<MetricsBucket>|null $unsubscribedBuckets
     * @param list<MetricsBucket>|null $unsubscribeDeliveredBuckets
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?string $startTime = null,
        public readonly ?string $endTime = null,
        public readonly ?int $unsubscribed = null,
        public readonly ?int $unsubscribeDelivered = null,
        public readonly ?array $unsubscribedBuckets = null,
        public readonly ?array $unsubscribeDeliveredBuckets = null,
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
            unsubscribed: ResponseValidator::asNullableInt($body, 'unsubscribed'),
            unsubscribeDelivered: ResponseValidator::asNullableInt($body, 'unsubscribe_delivered'),
            unsubscribedBuckets: MetricsBucket::listFromParent($buckets, 'unsubscribed'),
            unsubscribeDeliveredBuckets: MetricsBucket::listFromParent($buckets, 'unsubscribe_delivered'),
        );
    }
}
