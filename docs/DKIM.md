# DKIM And DNS

MailChannels supports both **hosted DKIM private keys** (MailChannels
generates and stores the key) and **customer-managed DKIM private keys**
(your application owns the key material and passes it in the send
payload). This guide walks through both, plus a Cloudflare DNS publication
recipe.

## Hosted DKIM Keys

MailChannels can generate and store DKIM private keys for your account.
This is the easiest way to avoid handling private key material in your
own application: create a key pair with the DKIM API, publish the
returned public DNS record, and reference the selector when sending.

```php
use MailChannels\Client;
use MailChannels\Enum\DkimAlgorithm;

$client = new Client(apiKey: getenv('MAILCHANNELS_API_KEY') ?: '');

$key = $client->dkim->create(
    domain: 'example.com',
    selector: 'mcdkim',
    algorithm: DkimAlgorithm::Rsa,
    keyLength: 2048,
);

foreach ($key->get('dkim_dns_records') ?? [] as $record) {
    echo "{$record['name']} {$record['type']} {$record['value']}\n";
}
```

MailChannels hosts the **private** key used for signing. It does *not*
host **public** keys in DNS for your domain — you must copy the returned
public DKIM TXT record into your own DNS zone. The record name will look
like `mcdkim._domainkey.example.com`.

After the DNS record is published, send mail with the selector. If
`dkim_domain` is omitted, MailChannels can derive it from the `from`
address, but setting it explicitly keeps the signing intent obvious:

```php
$client->emails->queue([
    'from' => ['email' => 'sender@example.com'],
    'to' => ['email' => 'recipient@example.net'],
    'subject' => 'DKIM signed message',
    'text' => 'This message is signed by a MailChannels-hosted DKIM key.',
    'dkim_domain' => 'example.com',
    'dkim_selector' => 'mcdkim',
]);
```

## List, Rotate, And Retire Hosted Keys

You can retrieve keys with their suggested DNS records, rotate active
keys to a fresh selector, and mark old keys retired once they're out of
the warmup window.

```php
use MailChannels\Enum\DkimUpdateStatus;

// Inspect existing keys + the DNS records you should have published.
$keys = $client->dkim->list(domain: 'example.com', includeDnsRecord: true);

// Rotate: create a replacement key under a new selector. Publish the
// returned record before sending any traffic under the new selector.
$rotated = $client->dkim->rotate('example.com', 'mcdkim', 'mcdkim2');

// Once `mcdkim2` is published and warm, retire the old selector so
// MailChannels stops signing with it.
$client->dkim->updateStatus('example.com', 'mcdkim', DkimUpdateStatus::Retired);
```

Allowed status transitions are `Retired`, `Revoked`, and `Rotated` —
see [`src/Enum/DkimUpdateStatus.php`](../src/Enum/DkimUpdateStatus.php).
There is no "Active" transition because keys are active immediately on
creation.

## Customer-Managed Private Keys

If you manage your own DKIM keys, pass `dkim_domain`, `dkim_selector`,
and the Base64-encoded `dkim_private_key` directly in the send payload.
Values set inside a personalization override root-level DKIM values for
that recipient.

```php
$client->emails->send([
    'from' => ['email' => 'sender@example.com'],
    'to' => 'recipient@example.net',
    'subject' => 'Customer-signed message',
    'text' => 'Signed with a key our application owns.',
    'dkim_domain' => 'example.com',
    'dkim_selector' => 'app2026',
    'dkim_private_key' => $base64PrivateKeyFromYourSecretStore,
]);
```

Treat the private key as a secret. Use your platform's secret manager
(AWS Secrets Manager, GCP Secret Manager, HashiCorp Vault, etc.) rather
than committing it to source control or environment files.

## Publish DKIM Records With Cloudflare

If your DNS lives in Cloudflare, the
[`examples/cloudflare-dkim.php`](../examples/cloudflare-dkim.php) script
shows an end-to-end recipe: create a MailChannels DKIM key, then create
or update the matching Cloudflare TXT record idempotently using
Cloudflare's REST API.

It expects four environment variables:

```bash
export MAILCHANNELS_API_KEY="your_mailchannels_api_key"
export MAILCHANNELS_DOMAIN="example.com"
export CLOUDFLARE_API_TOKEN="your_cloudflare_api_token"
export CLOUDFLARE_ZONE_ID="your_cloudflare_zone_id"
```

The Cloudflare token needs permission to read the zone and edit DNS
records — at least `Zone: Read` and `DNS: Edit` on the zone that owns
the sending domain.

The example uses raw cURL so the SDK doesn't grow a Cloudflare runtime
dependency. If your project already depends on a Cloudflare SDK, swap
the two helper functions at the bottom of the example for that
package's idioms.
