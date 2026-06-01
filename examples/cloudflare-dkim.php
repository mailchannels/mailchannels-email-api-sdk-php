<?php

declare(strict_types=1);

/**
 * End-to-end DKIM provisioning: create a MailChannels-hosted DKIM key,
 * then publish its DNS record to Cloudflare. Requires `cloudflare/sdk` or
 * any HTTP client; the publishCloudflareTxtRecord() function below is a
 * thin wrapper around Cloudflare's REST API so you can swap it for your
 * DNS provider of choice.
 */

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Enum\DkimAlgorithm;

$apiKey            = getenv('MAILCHANNELS_API_KEY');
$domain            = getenv('MAILCHANNELS_DOMAIN');
$cloudflareToken   = getenv('CLOUDFLARE_API_TOKEN');
$cloudflareZoneId  = getenv('CLOUDFLARE_ZONE_ID');
if (
    !is_string($apiKey) || $apiKey === ''
    || !is_string($domain) || $domain === ''
    || !is_string($cloudflareToken) || $cloudflareToken === ''
    || !is_string($cloudflareZoneId) || $cloudflareZoneId === ''
) {
    fwrite(STDERR, "Set MAILCHANNELS_API_KEY, MAILCHANNELS_DOMAIN, CLOUDFLARE_API_TOKEN, CLOUDFLARE_ZONE_ID.\n");
    exit(1);
}

$client = new Client(apiKey: $apiKey);
$selector = getenv('MAILCHANNELS_DKIM_SELECTOR') ?: 'mcdkim';

// 1) Create the DKIM key in MailChannels.
$created = $client->dkim->create(
    domain: $domain,
    selector: $selector,
    algorithm: DkimAlgorithm::Rsa,
    keyLength: 2048,
);
/** @var array{dkim_dns_records?: list<array{name: string, value: string}>} $payload */
$payload = $created->toArray();
$records = $payload['dkim_dns_records'] ?? [];
if ($records === []) {
    fwrite(STDERR, "MailChannels did not return a DKIM DNS record.\n");
    exit(1);
}
$record = $records[0];

// 2) Publish the DNS record to Cloudflare. publish_cloudflare_txt_record
//    creates the record if absent, updates it otherwise.
publishCloudflareTxtRecord(
    apiToken: $cloudflareToken,
    zoneId: $cloudflareZoneId,
    name: $record['name'],
    content: $record['value'],
);
echo "Published DKIM TXT record {$record['name']} via Cloudflare.\n";

/**
 * Idempotently create / update a TXT record in Cloudflare's DNS API.
 *
 * Replace this with your real Cloudflare client (e.g. `cloudflare/sdk` on
 * Packagist). The raw cURL form below avoids adding a dependency to this
 * SDK's examples directory.
 */
function publishCloudflareTxtRecord(string $apiToken, string $zoneId, string $name, string $content): void
{
    $base = "https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records";
    $headers = [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json',
    ];

    // Look up an existing record by name and type.
    $query = http_build_query(['type' => 'TXT', 'name' => $name]);
    $listing = cloudflareCall('GET', "{$base}?{$query}", $headers);
    /** @var array{result?: list<array{id: string}>} $listing */
    $existing = $listing['result'][0] ?? null;

    $body = json_encode([
        'type' => 'TXT',
        'name' => $name,
        'content' => $content,
        'ttl' => 1,
    ], JSON_THROW_ON_ERROR);

    if ($existing === null) {
        cloudflareCall('POST', $base, $headers, $body);
        return;
    }
    cloudflareCall('PUT', "{$base}/{$existing['id']}", $headers, $body);
}

/**
 * @param list<string> $headers
 * @return array<string, mixed>
 */
function cloudflareCall(string $method, string $url, array $headers, ?string $body = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("Cloudflare request failed: {$err}");
    }
    curl_close($ch);
    /** @var array<string, mixed> $decoded */
    $decoded = json_decode((string) $response, true, flags: JSON_THROW_ON_ERROR);
    return $decoded;
}
