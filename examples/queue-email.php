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

// /send-async returns immediately; delivery happens asynchronously on the
// MailChannels side. Use this for high-throughput senders.
$response = $client->emails->queue([
    'from'    => ['email' => 'sender@example.com'],
    'to'      => 'recipient@example.net',
    'subject' => 'Queued message',
    'html'    => '<strong>Hello from MailChannels</strong>',
]);

echo "Queued with message_id={$response->get('message_id')}\n";
