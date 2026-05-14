<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use DateTimeInterface;
use MailChannels\Enum\MetricsInterval;
use MailChannels\Enum\MetricsSenderType;
use MailChannels\Enum\MetricsSortOrder;
use MailChannels\Response;

final class Metrics extends AbstractResource
{
    public function engagement(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): Response {
        return $this->timeSeries('/metrics/engagement', $startTime, $endTime, $campaignId, $interval);
    }

    public function performance(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): Response {
        return $this->timeSeries('/metrics/performance', $startTime, $endTime, $campaignId, $interval);
    }

    public function recipientBehaviour(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): Response {
        return $this->timeSeries('/metrics/recipient-behaviour', $startTime, $endTime, $campaignId, $interval);
    }

    public function recipientBehavior(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): Response {
        return $this->recipientBehaviour($startTime, $endTime, $campaignId, $interval);
    }

    public function volume(
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?string $campaignId = null,
        MetricsInterval|string|null $interval = null,
    ): Response {
        return $this->timeSeries('/metrics/volume', $startTime, $endTime, $campaignId, $interval);
    }

    public function senders(
        MetricsSenderType|string $senderType,
        DateTimeInterface|string|null $startTime = null,
        DateTimeInterface|string|null $endTime = null,
        ?int $limit = null,
        ?int $offset = null,
        MetricsSortOrder|string|null $sortOrder = null,
    ): Response {
        $type = $senderType instanceof MetricsSenderType ? $senderType->value : $senderType;
        $query = self::compactOrNull([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'limit' => $limit,
            'offset' => $offset,
            'sort_order' => $sortOrder,
        ]);
        return $this->request('GET', "/metrics/senders/{$type}", null, $query);
    }

    private function timeSeries(
        string $path,
        DateTimeInterface|string|null $startTime,
        DateTimeInterface|string|null $endTime,
        ?string $campaignId,
        MetricsInterval|string|null $interval,
    ): Response {
        $query = self::compactOrNull([
            'start_time' => $startTime,
            'end_time' => $endTime,
            'campaign_id' => $campaignId,
            'interval' => $interval,
        ]);
        return $this->request('GET', $path, null, $query);
    }
}
