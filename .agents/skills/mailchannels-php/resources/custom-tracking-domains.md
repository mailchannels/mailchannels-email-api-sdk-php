# Custom Tracking Domains

Register your own hostname for click tracking, open tracking, or
unsubscribe links instead of MailChannels' shared domains. Once a domain
is `active`, select it by `name` via `custom_domain_name` on the send
payload — see [sending.md](sending.md#custom-tracking-domains).

## Register A Domain

```php
use MailChannels\Enum\CustomTrackingScope;

$domain = $client->customTrackingDomains->create(
    name: 'newsletter-clicks',     // label used at send time, ^[a-z0-9-]+$, max 64
    hostname: 'click.example.com',
    scope: CustomTrackingScope::Click, // Click | Open | Unsubscribe
);
```

Before this can complete, `hostname` needs a CNAME record pointing to
`links.mailchannels.net`.

## DNS Verification Is A Two-Step, Non-Error Flow

`create()` and `update()` (when re-activating a disabled domain) return a
single `CustomTrackingDomainResponse` covering two possible shapes — both
are 2xx responses, not errors:

- **Registered**: `name`/`hostname`/`scope`/`status`/`createdAt` are
  populated.
- **Pending**: `instructions` is populated (plus `token`/`txtRecordName`/
  `txtRecordValue` when a TXT ownership check is outstanding — a
  CNAME-only failure can omit those three).

```php
$domain = $client->customTrackingDomains->create(
    name: 'newsletter-clicks',
    hostname: 'click.example.com',
    scope: CustomTrackingScope::Click,
);

if ($domain->status === 'active') {
    echo "{$domain->hostname} ready as {$domain->name}\n";
} else {
    echo $domain->instructions . "\n";
    echo "{$domain->txtRecordName} = {$domain->txtRecordValue}\n";
}
```

Add the TXT record alongside the CNAME, wait for propagation, then call
`create()` again with the same `name`/`hostname`/`scope`.

If DNS still isn't in place on retry, MailChannels responds `422` instead
of `202` — that's a real error status, so the SDK raises
`InvalidRequestException` rather than returning a result. The same
pending-verification body is on `getResponseBody()`:

```php
use MailChannels\Exception\InvalidRequestException;

try {
    $client->customTrackingDomains->create(
        name: 'newsletter-clicks',
        hostname: 'click.example.com',
        scope: CustomTrackingScope::Click,
    );
} catch (InvalidRequestException $e) {
    $body = $e->getResponseBody();
    $instructions = is_array($body) ? ($body['instructions'] ?? null) : null;
    if (is_string($instructions)) {
        echo $instructions;
    }
}
```

## List, Update, Delete

```php
use MailChannels\Enum\CustomTrackingStatus;

$domains = $client->customTrackingDomains->list(
    scope: CustomTrackingScope::Click,   // optional filter
    status: CustomTrackingStatus::Active, // optional filter
    limit: 100,
    offset: 0,
);

$client->customTrackingDomains->update(
    'click.example.com',
    CustomTrackingScope::Click,
    status: CustomTrackingStatus::Disabled, // or name: 'new-label' to rename
);

$client->customTrackingDomains->delete('click.example.com', CustomTrackingScope::Click);
```

Deleting a domain takes effect immediately — tracking links and
unsubscribe URLs already sent using that domain stop working right away.
Re-activating a domain whose DNS verification has since lapsed goes
through the same pending-result flow as `create()`.
