<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Message\Attachment;

$apiKey = getenv('MAILCHANNELS_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY in your environment.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);

// Fetch a remote file and attach it in one call. Content type is taken
// from the server's Content-Type header first, then falls back to a
// guess from the filename extension, then to application/octet-stream.
//
// The default timeout is 10s per fetch; pass `timeout: <seconds>` to
// override. Each fetch blocks the calling code, so keep timeouts short
// if you're attaching multiple files before send().
$response = $client->emails->queue([
    'from'    => ['email' => 'sender@example.com'],
    'to'      => 'recipient@example.net',
    'subject' => 'Your monthly report',
    'text'    => 'The requested report is attached.',
    'attachments' => [
        Attachment::fromUrl('https://example.com/files/report.pdf'),
        // Optional overrides:
        Attachment::fromUrl(
            url: 'https://example.com/assets/logo',  // no extension
            filename: 'logo.png',
            contentType: 'image/png',
            contentId: 'inline-logo',
        ),
    ],
]);

echo "Queued with message_id={$response->get('message_id')}\n";
