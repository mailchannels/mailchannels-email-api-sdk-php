<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Enum\SuppressionDeleteSource;
use MailChannels\Enum\SuppressionSource;

$apiKey    = getenv('MAILCHANNELS_API_KEY');
$recipient = getenv('SUPPRESSION_RECIPIENT');
if (!is_string($apiKey) || $apiKey === '' || !is_string($recipient) || $recipient === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY and SUPPRESSION_RECIPIENT.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);

// Create a non-transactional suppression. addToSubAccounts mirrors the
// entry into all child accounts as well as the parent.
$created = $client->suppressions->create(
    entries: [[
        'recipient' => $recipient,
        'suppression_types' => ['non-transactional'],
        'notes' => 'Example preference-center opt-out.',
    ]],
    addToSubAccounts: true,
);
echo "Created suppression:\n";
print_r($created->toArray());

// List API-created entries (excludes bounces / spam-report-driven entries).
$entries = $client->suppressions->list(
    source: SuppressionSource::Api,
    limit: 100,
    offset: 0,
);
echo "\nAPI-created entries:\n";
print_r($entries->toArray());

// Delete every suppression record for this recipient regardless of source.
$client->suppressions->delete($recipient, SuppressionDeleteSource::All);
echo "\nDeleted all suppression entries for {$recipient}.\n";
