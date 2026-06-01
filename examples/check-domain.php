<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;

$apiKey = getenv('MAILCHANNELS_API_KEY');
$domain = getenv('MAILCHANNELS_DOMAIN');
if (!is_string($apiKey) || $apiKey === '' || !is_string($domain) || $domain === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY and MAILCHANNELS_DOMAIN.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);

// Basic check: SPF, sender-domain DNS, Domain Lockdown, and the
// default-discovered DKIM selectors for this domain.
$result = $client->checkDomain->check($domain);
echo "Domain check verdict for {$domain}:\n";
print_r($result->toArray());

// Constrained check: verify one specific MailChannels-stored DKIM selector.
$selector = getenv('MAILCHANNELS_DKIM_SELECTOR') ?: 'mcdkim';
$narrow = $client->checkDomain->check(
    domain: $domain,
    dkimSettings: [['dkim_domain' => $domain, 'dkim_selector' => $selector]],
);
echo "\nDKIM-only check for selector {$selector}:\n";
print_r($narrow->toArray());
