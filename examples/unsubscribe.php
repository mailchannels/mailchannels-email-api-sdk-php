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

// Approach 1 — one-click unsubscribe link embedded in the HTML body via
// MailChannels' hosted unsubscribe URL placeholder. MailChannels substitutes
// the placeholder per recipient at send time; clicks land on a hosted page
// that records the opt-out.
$oneClick = $client->emails->queue([
    'from' => ['email' => 'sender@example.com'],
    'personalizations' => [['to' => [['email' => 'recipient@example.net']]]],
    'subject' => 'Newsletter',
    'content' => [
        [
            'type' => 'text/html',
            'value' => '<p>Hello</p>'
                . '<a href="{{ unsubscribe_url }}">Unsubscribe</a>',
            'template_type' => 'mustache',
        ],
    ],
]);
echo "Queued one-click unsubscribe message: message_id={$oneClick->get('message_id')}\n";

// Approach 2 — let MailChannels add RFC 8058 List-Unsubscribe and
// List-Unsubscribe-Post headers automatically. Marketing/promotional sends
// (transactional=false) get this treatment when a DKIM-signed domain is in
// scope.
$listHeaders = $client->emails->queue([
    'from' => ['email' => 'sender@example.com'],
    'personalizations' => [[
        'to' => [['email' => 'recipient@example.net']],
        'dkim_domain' => 'example.com',
        'dkim_selector' => 'mailchannels',
        'dkim_private_key' => '-----BEGIN PRIVATE KEY-----...',
    ]],
    'subject' => 'Newsletter',
    'text' => 'Hello',
    'transactional' => false,
]);
echo "Queued List-Unsubscribe message: message_id={$listHeaders->get('message_id')}\n";
