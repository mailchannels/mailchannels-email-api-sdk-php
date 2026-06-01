<?php

declare(strict_types=1);

/**
 * Manage MailChannels webhook subscriptions. For the *receiving* side
 * (verifying inbound webhook signatures), see webhook-verify.php in this
 * directory.
 */

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Enum\WebhookBatchStatus;

$apiKey   = getenv('MAILCHANNELS_API_KEY');
$endpoint = getenv('WEBHOOK_ENDPOINT');
if (!is_string($apiKey) || $apiKey === '' || !is_string($endpoint) || $endpoint === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY and WEBHOOK_ENDPOINT.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);

// 1) Enroll a webhook endpoint.
$created = $client->webhooks->create($endpoint);
echo "Enrolled webhook:\n";
print_r($created->toArray());

// 2) List enrolled webhooks.
$all = $client->webhooks->list();
echo "\nEnrolled webhooks:\n";
print_r($all->toArray());

// 3) Trigger a validation event to confirm the endpoint round-trips signed
//    notifications. Useful right after enrollment or after deploying changes
//    to your receiver.
$validation = $client->webhooks->validate(requestId: 'example_validation');
echo "\nValidation kicked off:\n";
print_r($validation->toArray());

// 4) Look at recent batches that failed delivery (4xx / 5xx / no_response).
//    Use resendBatch() to re-deliver a specific failed batch.
$failed = $client->webhooks->batches(
    statuses: [
        WebhookBatchStatus::ClientError,
        WebhookBatchStatus::ServerError,
        WebhookBatchStatus::NoResponse,
    ],
    limit: 50,
);
echo "\nFailed batches:\n";
print_r($failed->toArray());
