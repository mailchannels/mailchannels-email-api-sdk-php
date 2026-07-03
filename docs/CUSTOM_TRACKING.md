# Custom Tracking Domains

By default, MailChannels rewrites click tracking links, open tracking
pixels, and unsubscribe URLs to use shared `mailchannels.net` domains.
Registering a custom tracking domain lets you replace those shared domains
with your own branded hostname (e.g. `click.example.com`) for one of three
scopes: `click`, `open`, or `unsubscribe`.

## Register A Domain

Registration needs a CNAME record in place before it can complete:

```php
use MailChannels\Enum\CustomTrackingScope;

$domain = $client->customTrackingDomains->create(
    name: 'newsletter-clicks',   // label used to select this domain at send time
    hostname: 'click.example.com',
    scope: CustomTrackingScope::Click,
);
```

Add a CNAME record for `click.example.com` pointing to
`links.mailchannels.net` before calling `create()`.

## DNS Ownership Verification

The first time a hostname is registered, MailChannels also requires proving
ownership via a TXT record — and the verification token for that TXT
record is generated and returned *by* the first `create()` call, so a
brand-new hostname can never come back active on that first call.
`create()` (and `update()`, when re-activating a disabled domain) return a
`CustomTrackingDomainResponse` either way; both the registered and pending
shapes come back through this one class, so check which fields are present
rather than assuming success:

```php
$domain = $client->customTrackingDomains->create(
    name: 'newsletter-clicks',
    hostname: 'click.example.com',
    scope: CustomTrackingScope::Click,
);

if ($domain->status === 'active') {
    echo "{$domain->hostname} is ready to use as {$domain->name}\n";
} else {
    echo $domain->instructions . "\n";
    echo "Add this TXT record: {$domain->txtRecordName} = {$domain->txtRecordValue}\n";
}
```

Add the TXT record alongside the CNAME record, wait for DNS propagation,
then call `create()` again with the same `name`/`hostname`/`scope` to
complete registration.

If the DNS records still aren't in place when you retry, MailChannels
returns a `422` instead of a `202` — that's a real error status, so the SDK
raises `InvalidRequestException` for it, with the same pending-verification
body available via `getResponseBody()`:

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

## Use A Custom Domain At Send Time

Once a domain is active, select it by `name` in `tracking_settings` (for
click/open tracking) or `unsubscribe_settings` (for unsubscribe links):

```php
$client->emails->send([
    'from' => 'sender@example.com',
    'to' => 'recipient@example.net',
    'subject' => 'Newsletter',
    'text' => 'Hello',
    'tracking_settings' => [
        'click_tracking' => ['enable' => true, 'custom_domain_name' => 'newsletter-clicks'],
    ],
    'unsubscribe_settings' => ['custom_domain_name' => 'newsletter-unsubscribe'],
]);
```

`custom_domain_name` must name a domain that is currently `active` for that
scope — the API contract doesn't document what happens if it names a
domain that's still pending verification or disabled, so don't rely on a
specific fallback behavior; verify the domain is `active` before
referencing it in a send.

## List, Update, Delete

```php
use MailChannels\Enum\CustomTrackingStatus;

$domains = $client->customTrackingDomains->list(
    scope: CustomTrackingScope::Click,
    status: CustomTrackingStatus::Active,
);

$client->customTrackingDomains->update(
    'click.example.com',
    CustomTrackingScope::Click,
    status: CustomTrackingStatus::Disabled,
);

$client->customTrackingDomains->delete('click.example.com', CustomTrackingScope::Click);
```

`update()` can rename a domain (`name:`) or toggle it between `active` and
`disabled`; re-activating a domain that was disabled long enough to lose
DNS verification returns the same pending-verification shape as `create()`.

Deleting a domain takes effect immediately — tracking links and unsubscribe
URLs in previously sent emails that reference the deleted domain will stop
working right away.
