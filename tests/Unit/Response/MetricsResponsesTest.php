<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\MetricsBucket;
use MailChannels\Response\MetricsEngagementResponse;
use MailChannels\Response\MetricsPerformanceResponse;
use MailChannels\Response\MetricsRecipientBehaviourResponse;
use MailChannels\Response\MetricsSender;
use MailChannels\Response\MetricsSenderResponse;
use MailChannels\Response\MetricsVolumeResponse;
use PHPUnit\Framework\TestCase;

final class MetricsResponsesTest extends TestCase
{
    public function testEngagementResponseExtractsTopLevelAndBuckets(): void
    {
        $body = [
            'start_time' => '2026-05-01T00:00:00Z',
            'end_time' => '2026-05-31T00:00:00Z',
            'open' => 100,
            'click' => 50,
            'open_tracking_delivered' => 200,
            'click_tracking_delivered' => 180,
            'buckets' => [
                'open' => [
                    ['period_start' => '2026-05-01T00:00:00Z', 'count' => 60],
                    ['period_start' => '2026-05-02T00:00:00Z', 'count' => 40],
                ],
                'click' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 50]],
                'open_tracking_delivered' => [],
                'click_tracking_delivered' => [],
            ],
        ];

        $typed = new MetricsEngagementResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame('2026-05-01T00:00:00Z', $typed->startTime);
        $this->assertSame('2026-05-31T00:00:00Z', $typed->endTime);
        $this->assertSame(100, $typed->open);
        $this->assertSame(50, $typed->click);
        $this->assertSame(200, $typed->openTrackingDelivered);
        $this->assertSame(180, $typed->clickTrackingDelivered);

        $this->assertNotNull($typed->openBuckets);
        $this->assertCount(2, $typed->openBuckets);
        $this->assertInstanceOf(MetricsBucket::class, $typed->openBuckets[0]);
        $this->assertSame(60, $typed->openBuckets[0]->count);
        $this->assertSame('2026-05-01T00:00:00Z', $typed->openBuckets[0]->periodStart);

        $this->assertNotNull($typed->clickBuckets);
        $this->assertCount(1, $typed->clickBuckets);
        $this->assertSame([], $typed->openTrackingDeliveredBuckets);
        $this->assertSame([], $typed->clickTrackingDeliveredBuckets);
    }

    public function testEngagementResponseLeavesFieldsNullWhenBodyEmpty(): void
    {
        $typed = new MetricsEngagementResponse(new Response(data: [], statusCode: 200));

        $this->assertNull($typed->open);
        $this->assertNull($typed->openBuckets);
        $this->assertNull($typed->startTime);
    }

    public function testEngagementResponseRejectsWrongTypeOpen(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'open' must be an int");
        new MetricsEngagementResponse(new Response(
            data: ['open' => '100'],
            statusCode: 200,
        ));
    }

    public function testPerformanceResponseExtractsFields(): void
    {
        $body = [
            'processed' => 1000,
            'delivered' => 950,
            'bounced' => 50,
            'buckets' => [
                'processed' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 1000]],
                'delivered' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 950]],
                'bounced' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 50]],
            ],
        ];

        $typed = new MetricsPerformanceResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame(1000, $typed->processed);
        $this->assertSame(950, $typed->delivered);
        $this->assertSame(50, $typed->bounced);
        $this->assertNotNull($typed->bouncedBuckets);
        $this->assertCount(1, $typed->bouncedBuckets);
        $this->assertSame(50, $typed->bouncedBuckets[0]->count);
    }

    public function testRecipientBehaviourResponseExtractsFields(): void
    {
        $body = [
            'unsubscribed' => 5,
            'unsubscribe_delivered' => 100,
            'buckets' => [
                'unsubscribed' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 5]],
                'unsubscribe_delivered' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 100]],
            ],
        ];

        $typed = new MetricsRecipientBehaviourResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame(5, $typed->unsubscribed);
        $this->assertSame(100, $typed->unsubscribeDelivered);
        $this->assertNotNull($typed->unsubscribedBuckets);
        $this->assertCount(1, $typed->unsubscribedBuckets);
    }

    public function testVolumeResponseExtractsFields(): void
    {
        $body = [
            'processed' => 1000,
            'delivered' => 950,
            'dropped' => 30,
            'buckets' => [
                'processed' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 1000]],
                'delivered' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 950]],
                'dropped' => [['period_start' => '2026-05-01T00:00:00Z', 'count' => 30]],
            ],
        ];

        $typed = new MetricsVolumeResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame(1000, $typed->processed);
        $this->assertSame(950, $typed->delivered);
        $this->assertSame(30, $typed->dropped);
        $this->assertNotNull($typed->droppedBuckets);
    }

    public function testSenderResponseExtractsTypedSenders(): void
    {
        $body = [
            'start_time' => '2026-05-01T00:00:00Z',
            'end_time' => '2026-05-31T00:00:00Z',
            'limit' => 50,
            'offset' => 0,
            'total' => 2,
            'senders' => [
                [
                    'name' => 'newsletter-2026',
                    'processed' => 10000,
                    'delivered' => 9500,
                    'bounced' => 500,
                    'dropped' => 0,
                ],
                [
                    'name' => 'promo-spring',
                    'processed' => 5000,
                    'delivered' => 4900,
                    'bounced' => 100,
                    'dropped' => 0,
                ],
            ],
        ];

        $typed = new MetricsSenderResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame(50, $typed->limit);
        $this->assertSame(0, $typed->offset);
        $this->assertSame(2, $typed->total);
        $this->assertNotNull($typed->senders);
        $this->assertCount(2, $typed->senders);
        $this->assertInstanceOf(MetricsSender::class, $typed->senders[0]);
        $this->assertSame('newsletter-2026', $typed->senders[0]->name);
        $this->assertSame(10000, $typed->senders[0]->processed);
        $this->assertSame(9500, $typed->senders[0]->delivered);
        $this->assertSame(500, $typed->senders[0]->bounced);
        $this->assertSame(0, $typed->senders[0]->dropped);
    }

    public function testSenderResponseRejectsSendersNotAList(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'senders' must be a list");
        new MetricsSenderResponse(new Response(
            data: ['senders' => ['name' => 'oops']],
            statusCode: 200,
        ));
    }

    public function testBucketsAsNonAssocAreRejected(): void
    {
        // The `buckets` field must be an object, not a list.
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'buckets' must be an associative array");
        new MetricsEngagementResponse(new Response(
            data: ['buckets' => ['open', 'click']],
            statusCode: 200,
        ));
    }

    public function testBucketEntryRejectsWrongTypeCount(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'count' must be an int");
        new MetricsEngagementResponse(new Response(
            data: ['buckets' => ['open' => [['count' => 'one hundred']]]],
            statusCode: 200,
        ));
    }

    public function testHttpMetadataPreservedAcrossAllResponseClasses(): void
    {
        $rawResponse = new Response(
            data: [],
            statusCode: 200,
            headers: ['X-Request-ID' => 'rid-metrics'],
        );

        $engagement = new MetricsEngagementResponse($rawResponse);
        $this->assertSame('rid-metrics', $engagement->requestId());

        $performance = new MetricsPerformanceResponse($rawResponse);
        $this->assertSame('rid-metrics', $performance->requestId());

        $recipient = new MetricsRecipientBehaviourResponse($rawResponse);
        $this->assertSame('rid-metrics', $recipient->requestId());

        $volume = new MetricsVolumeResponse($rawResponse);
        $this->assertSame('rid-metrics', $volume->requestId());

        $senders = new MetricsSenderResponse($rawResponse);
        $this->assertSame('rid-metrics', $senders->requestId());
    }

    public function testMetricsBucketJsonSerializeOmitsNullFields(): void
    {
        $bucket = new MetricsBucket(count: 42);

        $this->assertSame(['count' => 42], $bucket->jsonSerialize());
    }

    public function testMetricsSenderJsonSerializeRoundTripsAllFields(): void
    {
        $sender = new MetricsSender(
            name: 'newsletter-2026',
            processed: 100,
            delivered: 95,
            bounced: 5,
            dropped: 0,
        );

        $this->assertSame(
            ['name' => 'newsletter-2026', 'processed' => 100, 'delivered' => 95, 'bounced' => 5, 'dropped' => 0],
            $sender->jsonSerialize(),
        );
    }
}
