<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Enum\MetricsInterval;
use MailChannels\Enum\MetricsSenderType;
use MailChannels\Enum\MetricsSortOrder;

$apiKey = getenv('MAILCHANNELS_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY in your environment.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);
$startTime = getenv('MAILCHANNELS_METRICS_START') ?: '2026-04-01';
$endTime   = getenv('MAILCHANNELS_METRICS_END') ?: '2026-04-30T23:59:59Z';

// Engagement (opens/clicks) bucketed by day.
$engagement = $client->metrics->engagement(
    startTime: $startTime,
    endTime: $endTime,
    interval: MetricsInterval::Day,
);
echo "Engagement by day:\n";
print_r($engagement->toArray());

// Volume bucketed by week.
$volume = $client->metrics->volume(
    startTime: $startTime,
    endTime: $endTime,
    interval: MetricsInterval::Week,
);
echo "\nVolume by week:\n";
print_r($volume->toArray());

// Top 50 senders by campaign, descending.
$senders = $client->metrics->senders(
    senderType: MetricsSenderType::Campaigns,
    startTime: $startTime,
    endTime: $endTime,
    limit: 50,
    offset: 0,
    sortOrder: MetricsSortOrder::Descending,
);
echo "\nTop senders (campaigns):\n";
print_r($senders->toArray());
