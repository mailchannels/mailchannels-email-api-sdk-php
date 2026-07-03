<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Enum\CustomTrackingScope;
use MailChannels\Enum\CustomTrackingStatus;

$apiKey = getenv('MAILCHANNELS_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY in your environment.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);
$hostname = getenv('MAILCHANNELS_TRACKING_HOSTNAME') ?: 'click.example.com';

// Register a hostname for click tracking. The hostname needs a CNAME
// record pointing to links.mailchannels.net before this can complete.
$created = $client->customTrackingDomains->create(
    name: 'example-clicks',
    hostname: $hostname,
    scope: CustomTrackingScope::Click,
);

if ($created->status === 'active') {
    echo "{$hostname} is ready to use as {$created->name}\n";
} else {
    // DNS ownership verification is still pending — add the TXT record
    // described here alongside the CNAME, then run this script again.
    echo $created->instructions . "\n";
    echo "Add this TXT record: {$created->txtRecordName} = {$created->txtRecordValue}\n";
}

// List active custom tracking domains for every scope.
$domains = $client->customTrackingDomains->list(status: CustomTrackingStatus::Active);
echo "\nActive custom tracking domains:\n";
foreach ($domains->customTrackingDomains ?? [] as $domain) {
    echo "  {$domain->hostname} ({$domain->scope})\n";
}

// Disable the domain without deleting it.
$client->customTrackingDomains->update(
    $hostname,
    CustomTrackingScope::Click,
    status: CustomTrackingStatus::Disabled,
);
echo "\nDisabled {$hostname}.\n";
