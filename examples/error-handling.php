<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Exception\AuthenticationException;
use MailChannels\Exception\ForbiddenException;
use MailChannels\Exception\MailChannelsException;
use MailChannels\Exception\PayloadTooLargeException;
use MailChannels\Exception\RateLimitException;
use MailChannels\Exception\TransportException;
use MailChannels\Exception\ValidationException;

$apiKey = getenv('MAILCHANNELS_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY in your environment.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);

try {
    $response = $client->emails->send([
        'from'    => ['email' => 'sender@example.com'],
        'to'      => 'recipient@example.net',
        'subject' => 'Error handling example',
        'text'    => 'This message demonstrates structured error handling.',
    ]);
    $first = $response->results[0] ?? null;
    echo "Sent: request_id={$response->requestId()}, message_id={$first?->messageId}\n";
} catch (ValidationException $e) {
    // The SDK throws this BEFORE making any HTTP call when payload shape
    // is wrong. Fix your code, not your retry policy.
    fwrite(STDERR, "Bad payload: {$e->getMessage()}\n");
    exit(2);
} catch (AuthenticationException $e) {
    fwrite(STDERR, "API key is invalid or missing (status {$e->getStatusCode()}).\n");
    exit(3);
} catch (ForbiddenException $e) {
    fwrite(STDERR, "Permission error: {$e->getMessage()}\n");
    exit(4);
} catch (PayloadTooLargeException $e) {
    fwrite(STDERR, "Message too large; shrink attachments or split recipients.\n");
    exit(5);
} catch (RateLimitException $e) {
    // Retry-After is parsed off the 429 response when present.
    $retry = $e->getRetryAfter() ?? 'unspecified';
    fwrite(STDERR, "Rate limited; retry after {$retry}s.\n");
    exit(6);
} catch (TransportException $e) {
    // Network failure before MailChannels saw the request — safe to retry.
    fwrite(STDERR, "Transport error: {$e->getMessage()}\n");
    exit(7);
} catch (MailChannelsException $e) {
    // Catch-all for any other SDK exception. toArray() returns the
    // structured metadata (status, code, type, request id, headers, etc.)
    // suitable for one-line JSON logging.
    fwrite(STDERR, "MailChannels error: " . json_encode($e->toArray()) . "\n");
    exit(99);
}
