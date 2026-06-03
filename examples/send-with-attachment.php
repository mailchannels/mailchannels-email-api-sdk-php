<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Message\Attachment;
use MailChannels\Message\Content;
use MailChannels\Message\EmailAddress;
use MailChannels\Message\EmailParams;
use MailChannels\Message\Personalization;

$apiKey = getenv('MAILCHANNELS_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY in your environment.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);

$params = new EmailParams(
    from: new EmailAddress('sender@example.com', 'Receipts'),
    subject: 'Your receipt',
    personalizations: [new Personalization(to: ['customer@example.net'])],
    content: [Content::html('<p>Thanks for your order. The receipt is attached.</p>')],
    attachments: [Attachment::fromBytes('PDF-bytes-here', 'receipt.pdf', 'application/pdf')],
    transactional: true,
);

$response = $client->emails->send($params);

$first = $response->results[0] ?? null;
echo "Accepted (HTTP {$response->statusCode}), request_id={$response->requestId()}, message_id={$first?->messageId}\n";
