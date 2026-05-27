<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
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

// Mustache templates: ship one content block, then per-personalization
// data fills the {{ placeholders }}. Two recipients, two different greetings.
$params = new EmailParams(
    from: new EmailAddress('sender@example.com'),
    subject: 'Template example',
    personalizations: [
        new Personalization(
            to: ['jane@example.net'],
            dynamicTemplateData: ['name' => 'Jane Doe'],
        ),
        new Personalization(
            to: ['john@example.net'],
            dynamicTemplateData: ['name' => 'John Smith'],
        ),
    ],
    content: [
        new Content(
            type: Content::TEXT_PLAIN,
            value: 'Hello {{name}}',
            templateType: Content::TEMPLATE_MUSTACHE,
        ),
    ],
);

// Dry-run so you can preview the rendered payload without delivering.
$preview = $client->emails->send($params, dryRun: true);
echo "Template preview (dry-run):\n";
print_r($preview->toArray());
