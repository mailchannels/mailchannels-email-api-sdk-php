<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;

$apiKey = getenv('MAILCHANNELS_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY in your environment.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);

// Parent-account usage for the current billing period.
$accountUsage = $client->usage->retrieve();
echo "Parent account usage:\n";
print_r($accountUsage->toArray());

// Per-sub-account usage, if a handle is set in the environment.
$handle = getenv('MAILCHANNELS_SUB_ACCOUNT_HANDLE');
if (is_string($handle) && $handle !== '') {
    $subAccountUsage = $client->subAccounts->retrieveUsage($handle);
    echo "\nSub-account usage for {$handle}:\n";
    print_r($subAccountUsage->toArray());
}
