<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Enum\DkimAlgorithm;
use MailChannels\Enum\DkimUpdateStatus;

$apiKey = getenv('MAILCHANNELS_API_KEY');
$domain = getenv('MAILCHANNELS_DOMAIN');
if (!is_string($apiKey) || $apiKey === '' || !is_string($domain) || $domain === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY and MAILCHANNELS_DOMAIN.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);
$selector = getenv('MAILCHANNELS_DKIM_SELECTOR') ?: 'mcdkim';

// Create a MailChannels-hosted DKIM key pair. MailChannels stores the
// private key; you publish the returned public DNS record.
$created = $client->dkim->create(
    domain: $domain,
    selector: $selector,
    algorithm: DkimAlgorithm::Rsa,
    keyLength: 2048,
);
echo "Created DKIM key for {$domain}/{$selector}:\n";
print_r($created->toArray());

// List keys with the DNS records you need to publish.
$keys = $client->dkim->list(domain: $domain, includeDnsRecord: true);
echo "\nAll DKIM keys for {$domain}:\n";
print_r($keys->toArray());

// Rotate to a new selector when you need to retire the current one.
$nextSelector = $selector . '-' . date('Ymd');
$rotated = $client->dkim->rotate($domain, $selector, $nextSelector);
echo "\nRotated to {$nextSelector}:\n";
print_r($rotated->toArray());

// Once the new selector is published and warm, retire the old one so
// MailChannels stops signing with it.
$client->dkim->updateStatus($domain, $selector, DkimUpdateStatus::Retired);
echo "\nRetired {$selector}.\n";
