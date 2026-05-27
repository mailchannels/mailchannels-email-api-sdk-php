<?php

declare(strict_types=1);

/**
 * Inject a specific PSR-18 client + PSR-17 factories instead of letting the
 * SDK auto-discover. Useful when:
 *   - You have multiple PSR-18 clients installed and want to pin one,
 *   - You want to attach Guzzle middleware (retries, logging, etc.),
 *   - You're running behind a corporate proxy that needs custom config.
 *
 * Two interchangeable client setups are shown below; uncomment the one
 * matching the PSR-18 implementation you have installed.
 */

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;

$apiKey = getenv('MAILCHANNELS_API_KEY');
if (!is_string($apiKey) || $apiKey === '') {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY in your environment.\n");
    exit(1);
}

// Option A — Guzzle. Provides both the PSR-18 client and PSR-17 factories.
$guzzle  = new GuzzleHttp\Client(['timeout' => 10.0]);
$factory = new GuzzleHttp\Psr7\HttpFactory();
$client  = new Client(
    apiKey: $apiKey,
    httpClient: $guzzle,
    requestFactory: $factory,
    streamFactory: $factory,
);

// Option B — Symfony HTTP Client + nyholm/psr7.
// $symfony = (new Symfony\Component\HttpClient\Psr18Client(
//     Symfony\Component\HttpClient\HttpClient::create(['timeout' => 10.0]),
// ));
// $factory = new Nyholm\Psr7\Factory\Psr17Factory();
// $client = new Client(
//     apiKey: $apiKey,
//     httpClient: $symfony,
//     requestFactory: $factory,
//     streamFactory: $factory,
// );

$response = $client->usage->retrieve();
echo "Usage via injected HTTP client:\n";
print_r($response->toArray());
