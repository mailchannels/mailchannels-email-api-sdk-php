# DKIM Keys

MailChannels can host the **private** DKIM key for you. You publish the
**public** key in the domain's DNS as a TXT record. The public DNS record is
**not** hosted by MailChannels — you must publish it yourself in whatever DNS
provider holds the zone (e.g. Route53, Cloudflare).

## Create A Hosted Key

```php
use MailChannels\Enum\DkimAlgorithm;

$created = $client->dkim->create(
    'example.com',
    selector: 'mcdkim',
    algorithm: DkimAlgorithm::Rsa, // only RSA is currently supported
    keyLength: 2048,                // 1024 or 2048; default 2048
);

foreach ($created->key->dkimDnsRecords ?? [] as $record) {
    echo "{$record->name} {$record->type} {$record->value}\n";
}
```

The returned `$created->key->dkimDnsRecords` contains TXT records you must publish.

## List, Filter, And Include DNS Records

```php
use MailChannels\Enum\DkimKeyStatus;

$keys = $client->dkim->list(
    'example.com',
    selector: 'mcdkim',                  // optional; returns at most one
    status: DkimKeyStatus::Active,        // Active | Retired | Revoked | Rotated
    includeDnsRecord: true,              // include suggested DNS record per key
    limit: 10,
    offset: 0,
);
```

## Lifecycle

A DKIM key has one of four statuses:

| Status | Meaning |
| --- | --- |
| `Active` | Currently used for signing. |
| `Rotated` | Being rotated out. Still valid for signing for a 3-day grace period; auto-changes to `Retired` 2 weeks after rotation. |
| `Retired` | No longer in use. |
| `Revoked` | Marked compromised. Stop using immediately. |

### Update Status Directly

```php
use MailChannels\Enum\DkimUpdateStatus;

$client->dkim->updateStatus('example.com', 'mcdkim', DkimUpdateStatus::Revoked);
```

Only `Revoked`, `Retired`, and `Rotated` are valid update targets. Only
`Active` keys can move to `Rotated`.

### Rotate (Recommended For Routine Rollover)

```php
$result = $client->dkim->rotate(
    'example.com',
    'mcdkim',   // old selector — marked Rotated
    'mcdkim2',  // selector for the freshly-issued key
);

$result->newKey; // the new DkimKeyInfo; valid for signing immediately
$result->rotatedKey?->gracePeriodExpiresAt; // old key will no longer be used for signing after this time
```

Rotation:

1. Marks the existing key `Rotated` (still valid for **3 days** — exact
   cut-off in `gracePeriodExpiresAt`).
2. Creates a new `Active` key with the new selector, reusing the same
   algorithm and key length.
3. The rotated key is auto-retired **2 weeks** after rotation.

**Publish the new DNS record before `gracePeriodExpiresAt`** or emails
signed with the new key will fail DKIM at receiving providers.

## Sending With A Hosted Key

```php
$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        subject: 'Signed',
        content: [Content::plain('Signed by hosted DKIM')],
        dkimDomain: 'example.com',
        dkimSelector: 'mcdkim',
    )
);
```

If `dkimSelector` is set without `dkimDomain`, MailChannels takes the
domain from `from.email`.

## Sending With A Customer-Managed Key

If you keep the private key yourself, pass it Base64-encoded:

```php
$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        subject: 'Signed',
        content: [Content::plain('Signed by my own DKIM key')],
        dkimDomain: 'example.com',
        dkimSelector: 'mcdkim',
        dkimPrivateKey: '<base64-encoded-private-key>',
    )
);
```

Any of `dkimDomain` / `dkimSelector` / `dkimPrivateKey` can be set per
personalization to override the root values.

## DNS Publication

MailChannels does not host the public DKIM DNS record — you do. The SDK
returns the exact record to publish in `dkimDnsRecords`:

```php
$created = $client->dkim->create('example.com', selector: 'mcdkim');

foreach ($created->key->dkimDnsRecords ?? [] as $record) {
    echo "{$record->name}  {$record->type}  {$record->value}\n";
    // e.g.  mcdkim._domainkey.example.com  TXT  "v=DKIM1; k=rsa; p=MIIBIj..."
}
```

Every record has the same shape across every DNS provider:

| Field | Value |
| --- | --- |
| `name` | The DNS host. Always `{selector}._domainkey.{domain}`. |
| `type` | Always `TXT`. |
| `value` | The public key material as a quoted TXT string. **Publish the value verbatim** — do not re-wrap, strip quotes, or split lines. |

### Required Steps

1. **Generate the key** with `$client->dkim->create($domain, selector: ...)`.
   Capture `dkimDnsRecords`.
2. **Resolve the DNS zone** that hosts `domain` in your provider's API.
3. **Look up any existing TXT record** at `$record->name` in that zone so the
   publication is idempotent.
4. **Create or update** the TXT record with `content = $record->value`. Use a
   short TTL (300 s) so future rotations propagate quickly.
5. **Wait for propagation**, then verify with
   `$client->checkDomain->check($domain, dkimSettings: [...])`. The `dkim`
   verdict must be `passed` before you start signing real traffic.
6. **For rotations**, leave the rotated key's TXT record in place until
   `gracePeriodExpiresAt` on the old key has passed.

## Selector Format

- 1–63 characters.
- Lowercase letters, numbers, and `-` work everywhere. Avoid `_` in the
  selector itself; it's only used in the DNS host name.
