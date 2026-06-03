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

$response = $client->emails->send([
    'from'    => ['email' => 'sender@example.com', 'name' => 'Priya Patel'],
    'to'      => 'recipient@example.net',
    'subject' => 'Hello from the MailChannels PHP SDK',
    'text'    => 'It works.',
    'html'    => '<p>It works.</p>',
]);

$first = $response->results[0] ?? null;
echo "Accepted (HTTP {$response->statusCode}), request_id={$response->requestId()}, message_id={$first?->messageId}\n";
