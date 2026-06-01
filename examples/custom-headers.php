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

// 1) Top-level headers: applied to every personalization.
$response = $client->emails->send([
    'from'    => ['email' => 'sender@example.com'],
    'to'      => 'recipient@example.net',
    'subject' => 'Custom header example',
    'text'    => 'This email includes custom headers.',
    'headers' => [
        'List-Unsubscribe' => '<mailto:unsubscribe@example.com>',
        'X-Campaign-ID'    => 'newsletter-123',
    ],
], dryRun: true);
echo "Top-level headers (dry-run):\n";
print_r($response->toArray());

// 2) Per-personalization headers: each recipient gets their own header set.
$response = $client->emails->send([
    'from'    => ['email' => 'sender@example.com'],
    'subject' => 'Bananas are on sale',
    'personalizations' => [
        [
            'to' => [['email' => 'banana-lover@example.net']],
            'headers' => [
                'List-Unsubscribe' => '<mailto:unsubscribe@bananas.example>',
                'X-Custom-Header'  => 'BananaFan123',
            ],
        ],
    ],
    'content' => [
        ['type' => 'text/plain', 'value' => 'This email includes custom headers.'],
    ],
], dryRun: true);
echo "\nPer-personalization headers (dry-run):\n";
print_r($response->toArray());
