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
//
// The ack carries a request id and a queued-at timestamp — not a message
// id (the message hasn't been delivered yet). Per-message identifiers
// arrive later via webhook events.
$response = $client->emails->queue([
    'from'    => ['email' => 'sender@example.com'],
    'to'      => 'recipient@example.net',
    'subject' => 'Queued message',
    'html'    => '<strong>Hello from MailChannels</strong>',
]);

echo "Queued at={$response->queuedAt} request_id={$response->requestId()}\n";
