# Metrics And Usage

The metrics resource exposes the operational view of your traffic. Usage
exposes billing-period totals.

## Time-Series Metrics

The four time-series methods share the same parameter shape: optional
`startTime`, `endTime`, `campaignId`, and `interval`. (`senders()` is
grouped data with a different signature — see below.)

```php
use MailChannels\Enum\MetricsInterval;

$client->metrics->volume(
    startTime: '2026-04-01',
    endTime: '2026-05-01T00:00:00Z',
    interval: MetricsInterval::Day,   // Hour | Day | Week | Month (default Day)
    campaignId: 'welcome',            // optional filter
);

// Same parameter shape — pass the same arguments to any of these:
$client->metrics->engagement(interval: MetricsInterval::Day);          // open / click counts
$client->metrics->performance(interval: MetricsInterval::Day);         // delivered / bounced / processed
$client->metrics->recipientBehaviour(interval: MetricsInterval::Day);  // unsubscribes
// recipientBehavior() is an alias with American spelling — both work.
```

`startTime` and `endTime` accept ISO 8601 strings (`YYYY-MM-DD` or
`YYYY-MM-DDTHH:MM:SSZ`) or any `DateTimeInterface` object. Defaults:
`startTime` is one month ago, `endTime` is now.

### Buckets

Each response includes both totals **and** a `buckets` property grouped by
metric name, each containing a list of period-start / count pairs aligned to
`interval`.

```php
$result = $client->metrics->volume(interval: MetricsInterval::Day);

foreach ($result->processedBuckets ?? [] as $bucket) {
    echo $bucket->periodStart . ': ' . $bucket->count . "\n";
}
```

## Sender Metrics

`senders()` lists per-sender totals grouped either by campaign or sub-account.

```php
use MailChannels\Enum\MetricsSenderType;
use MailChannels\Enum\MetricsSortOrder;

$result = $client->metrics->senders(
    MetricsSenderType::Campaigns,   // Campaigns | SubAccounts
    startTime: '2026-04-01',
    endTime: '2026-05-01',
    limit: 5,                      // 1..1000, default 10
    offset: 0,
    sortOrder: MetricsSortOrder::Descending, // Ascending | Descending, default Descending
);

foreach ($result->senders ?? [] as $sender) {
    echo $sender->name . ': processed=' . $sender->processed . "\n";
}
```

Senders with **zero traffic in the time range are omitted** from the
response.

## Usage

Use `$client->usage->retrieve()` for parent-account usage in the current
billing period: (Note that parent-account usage includes all sub-accounts)

```php
$usage = $client->usage->retrieve();

echo $usage->totalUsage;       // int, messages sent this period
echo $usage->periodStartDate;  // ISO date string
echo $usage->periodEndDate;
echo $usage->monthlyLimit;     // effective cap for the period; 0 means
                                // the account can't send. For a
                                // sub-account with no explicit limit set,
                                // this already resolves to the parent
                                // account's limit — see sub-accounts.md
                                // for how that differs from
                                // limits->retrieve()'s -1.
```

For a specific sub-account, use `$client->subAccounts->retrieveUsage($handle)`
instead. See [sub-accounts](sub-accounts.md).

## Common Patterns

- **Daily dashboard**: `$client->metrics->volume(interval: MetricsInterval::Day)` plus
  `$client->metrics->engagement(interval: MetricsInterval::Day)` over the same range.
- **Campaign post-mortem**: pass `campaignId` on each call, then read both
  totals and buckets.
- **Top senders by sub-account**: `$client->metrics->senders(MetricsSenderType::SubAccounts,
  sortOrder: MetricsSortOrder::Descending)`.
