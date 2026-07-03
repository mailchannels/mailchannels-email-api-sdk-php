# Sending Email

Use `$client->emails->queue()` (`/send-async`) as the default. Switch to
`$client->emails->send()` (`/send`) only when the caller specifically needs
per-personalization results in the HTTP response, or the dry-run preview.

Build payloads with the typed `EmailParams` / `Personalization` /
`Content` / `EmailAddress` value objects — they validate at construction
time, narrow expected fields for static analysis (PHPStan), and make
AI-generated code easier to review. The SDK also accepts plain
associative arrays with Resend-style shortcuts (`to`, `html`, `text`) for
quick scripts, but typed objects are the recommended form.

**Always use named arguments** when constructing SDK objects — the
constructors have many optional parameters and positional calls are fragile
against future additions.

## Quickest Send (Array Form)

```php
use MailChannels\Client;

$client = new Client(); // reads MAILCHANNELS_API_KEY from env

$response = $client->emails->queue([
    'from'    => 'sender@example.com',
    'to'      => 'recipient@example.net',
    'subject' => 'Hello',
    'html'    => '<p>HTML body.</p>',
    'text'    => 'Plain text body.',
]);

echo "Queued at {$response->queuedAt}, request_id={$response->requestId()}\n";
```

The array form supports both Resend-style shortcuts (`to`, `cc`, `bcc`,
`html`, `text`) and the full MailChannels payload shape (`from`,
`subject`, `content`, `personalizations`).

## Quickest Send (Typed Object Form)

```php
use MailChannels\Client;
use MailChannels\Message\{EmailAddress, EmailParams, Personalization, Content};

$client = new Client();

$response = $client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com', 'Sender Name'),
        subject: 'Hello',
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        content: [
            Content::plain('Plain text body.'),
            Content::html('<p>HTML body.</p>'),
        ],
    )
);
```

A few conventions worth knowing:

- `personalizations` must have at least one entry. Each
  `Personalization` must have at least one `to` recipient.
- `content` is a list of `Content` parts. Provide both `text/plain` and
  `text/html` whenever possible — receiving clients render the last
  matching part and a plain-text fallback improves deliverability.
- `Content::plain(string $value)`, `Content::html(string $value)`, and
  `Content::mustacheHtml(string $value)` are convenient factory methods.

## Per-Recipient Personalization

`Personalization` is one fully-rendered outgoing message. To fan out to
multiple recipients with per-recipient variations, give each one its own
`Personalization`:

```php
$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        subject: 'Hello',
        personalizations: [
            new Personalization(to: ['first@example.net']),
            new Personalization(to: ['second@example.net']),
        ],
        content: [
            Content::plain('Plain'),
            Content::html('<p>HTML</p>'),
        ],
    )
);
```

Per-recipient overrides — subject, reply-to, headers, template variables,
DKIM settings — go on each `Personalization`. The top-level `from` /
`subject` / `content` act as defaults that each personalization may
override.

## Synchronous Send With Per-Personalization Results

Use `send()` when you need the message ID and delivery status for each
personalization in the HTTP response, or for dry-runs:

```php
$response = $client->emails->send($params);

foreach ($response->results ?? [] as $result) {
    echo "{$result->messageId} ({$result->status})\n";
    // status is "sent" or "failed"; "sent" is preliminary — final outcome via webhooks
}
```

## Dry Run (Send Endpoint Only)

`/send` supports `dryRun: true`. The API validates and renders the message
but does not deliver it. Useful for asserting templates render before launch:

```php
$preview = $client->emails->send($params, dryRun: true);

foreach ($preview->dryRunData ?? [] as $rendered) {
    echo $rendered, "\n"; // full RFC822 rendered message per personalization
}
```

`queue()` (i.e. `/send-async`) does **not** support dry-run.

## When To Pick Which

| Situation                                                                     | Method |
|-------------------------------------------------------------------------------| --- |
| Web request handler, background worker, high throughput                       | `queue()` |
| Sending to many recipients in one call                                        | `queue()` |
| You need the rendered message back for inspection                             | `send(dryRun: true)` |
| You need immediate per-personalization results for a small number of messages | `send()` |

## Client-Side Parallelism

The PHP SDK has no native async variants — both `send()` and `queue()` make
a synchronous HTTP call. For client-side parallelism use your PSR-18
client's concurrency features.

## Common Pitfalls

- **Reserved headers**: Don't set `From`, `To`, `Subject`, `Reply-To`,
  `Message-ID`, `Content-Type`, `DKIM-Signature`, etc. via `headers`. Use
  the payload fields instead. See [custom-headers](custom-headers.md).
- **Payload size limit**: 30 MB total including headers, body, and
  attachments. Exceeding this raises `PayloadTooLargeException`.
- **Per-personalization recipients**: up to 1000 entries in `to`, `cc`, `bcc`
  each; up to 1000 personalizations per send; up to 1000 attachments.
- **Non-transactional sends** (those that generate `List-Unsubscribe`
  headers) **must** have exactly one recipient per personalization and be
  DKIM-signed. See [unsubscribe](unsubscribe.md).
- **`campaignId`**: ≤ 48 UTF-8 characters and **no spaces** (server-side API limit — the SDK does not validate this client-side).
- **Click tracking** only rewrites `<a>` tags whose URL is non-empty, starts with `http`/`https`, and does not have `clicktracking="off"`.
- **No server-side idempotency.** Retrying after a transient failure can
  produce duplicate sends. Build idempotency into the caller (e.g. a
  unique `campaignId` + recipient-set check) when at-most-once delivery
  matters.

## Tracking

```php
$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        subject: 'Tracked',
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        content: [
            Content::html('<p>Hello <a href="https://example.com">click here</a></p>'),
        ],
        trackingSettings: [
            'open_tracking'  => ['enable' => true],
            'click_tracking' => ['enable' => true],
        ],
    )
);
```

Open and click tracking require a subscription that supports them.

### Custom Tracking Domains

Pass `custom_domain_name` inside a `tracking_settings` toggle (or inside
`unsubscribe_settings`) to route those links through a domain you
registered instead of the shared MailChannels domain. The name must match
an **active** custom tracking domain registered for that scope — see
[custom-tracking-domains](custom-tracking-domains.md) for registration and
DNS verification.

```php
$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        subject: 'Tracked with a custom domain',
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        content: [
            Content::html('<p>Hello <a href="https://example.com">click here</a></p>'),
        ],
        trackingSettings: [
            'click_tracking' => ['enable' => true, 'custom_domain_name' => 'newsletter-clicks'],
        ],
        unsubscribeSettings: ['custom_domain_name' => 'newsletter-unsubscribe'],
    )
);
```

`trackingSettings`/`unsubscribeSettings` are plain associative arrays on
both the array-payload and typed `EmailParams` forms — there's no
dedicated `TrackingSettings`/`UnsubscribeSettings` value class, unlike
`Personalization`/`Content`.

## Reading The Response

`queue()` returns a `QueuedSendResponse`:

```php
$response = $client->emails->queue($params);

$response->queuedAt;    // ?string — ISO 8601 timestamp
$response->requestId();       // ?string — ties webhook events back to this call
$response->statusCode;  // 202
$response->headers;     // array<string, string> — all response headers
$response['queued_at']; // array access also works
```

`send()` (non-dry-run) returns a `SendResponse`. `$response->results`
is a `list<SendResult>|null`, each with `index`, `messageId`, `status`
(`sent` / `failed`), and `reason`. **`sent` does not mean delivered** — the final
delivery outcome arrives via webhooks if you've enrolled.
