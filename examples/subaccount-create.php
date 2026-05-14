<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;

$client = new Client(apiKey: getenv('MAILCHANNELS_API_KEY') ?: '');

// Handles must contain only lowercase letters and digits. Hex from
// random_bytes() satisfies that pattern.
$handle = 'tenant' . bin2hex(random_bytes(4));

$client->subAccounts->create(companyName: 'ACME', handle: $handle);
$apiKey = $client->subAccounts->apiKeys->create($handle);
$client->subAccounts->limits->set($handle, sends: 50_000);

echo "Created sub-account: {$handle}\n";
echo "API key: {$apiKey->get('id')}\n";
