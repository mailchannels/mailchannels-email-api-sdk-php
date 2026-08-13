# Symfony Webhook Integration

The SDK ships a `symfony/webhook` request parser so you can receive
MailChannels delivery-event webhooks as typed
`Symfony\Component\RemoteEvent\RemoteEvent` objects instead of hand-rolling
signature verification and payload parsing (see [webhooks.md](../webhooks.md)
for the raw payload shape and manual verification flow this wraps). This
integration is optional — it requires `symfony/webhook`, `symfony/remote-event`,
and the `sodium` extension, none of which the SDK hard-requires.

## Installation

```bash
composer require symfony/webhook symfony/remote-event ext-sodium
```

NOTE: Requires symfony/webhooks version 7.2 or higher, as earlier versions do not support batched webhook processing.

## Setup

Register the parser as a service, e.g. in `config/services.yaml`:

```yaml
services:
    MailChannels\Plugins\Symfony\Webhook\MailChannelsRequestParser: ~
```

Route a webhook type to it in `config/webhooks.yaml`:

```yaml
framework:
    webhook:
        routing:
            mailchannels:
                service: MailChannels\Plugins\Symfony\Webhook\MailChannelsRequestParser
```

This exposes a route at `/webhook/mailchannels` — point your MailChannels
webhook enrollment at that URL (see [webhooks.md](../webhooks.md#enroll-and-manage)
for `$client->webhooks->create()`).

Symfony's webhook config requires a `secret` key per route, but
`MailChannelsRequestParser` never reads it — verification uses the Ed25519
signature and the public key fetched from `$client->webhooks->publicKey()`,
not an HMAC shared secret. Any value (including an empty string) satisfies
the config; there's nothing to keep secret here.

The parser constructs its own `MailChannels\Client` (reading credentials the
same way `new Client()` always does — see
[clients-and-transport.md](../clients-and-transport.md)) unless you wire one
in yourself, e.g.:

```yaml
services:
    MailChannels\Plugins\Symfony\Webhook\MailChannelsRequestParser:
        arguments:
            $client: '@my_configured_mailchannels_client'
```

## Consuming RemoteEvents

Tag a consumer with the routing name to receive parsed events:

```php
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerEngagementEvent;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('mailchannels')] // must match the routing name above
final class MailChannelsWebhookConsumer implements ConsumerInterface
{
    public function consume(RemoteEvent $event): void
    {
        match (true) {
            $event instanceof MailerDeliveryEvent => /* processed/delivered/bounce/deferred/dropped */ null,
            $event instanceof MailerEngagementEvent => /* open/click/complained/unsubscribed */ null,
            default => null, // MailChannels\Plugins\Symfony\RemoteEvent\TestEvent
        };
    }
}
```

For the general consumption mechanics (Messenger dispatch, multiple
consumers, etc.), see the
[Symfony Webhook documentation](https://symfony.com/doc/current/webhook.html#consuming-the-remoteevent).
This integration only supplies the parser + payload converter; consuming is
plain Symfony.

## Event Type Mapping

Each MailChannels `event` string is converted to a core Symfony mailer
`RemoteEvent` subclass and constant:

| MailChannels `event` | Symfony class            | Constant                          |
|-----------------------|---------------------------|------------------------------------|
| `processed`           | `MailerDeliveryEvent`     | `MailerDeliveryEvent::RECEIVED`    |
| `delivered`           | `MailerDeliveryEvent`     | `MailerDeliveryEvent::DELIVERED`   |
| `hard-bounced`        | `MailerDeliveryEvent`     | `MailerDeliveryEvent::BOUNCE`      |
| `soft-bounced`        | `MailerDeliveryEvent`     | `MailerDeliveryEvent::DEFERRED`    |
| `dropped`             | `MailerDeliveryEvent`     | `MailerDeliveryEvent::DROPPED`     |
| `open`                | `MailerEngagementEvent`   | `MailerEngagementEvent::OPEN`      |
| `click`               | `MailerEngagementEvent`   | `MailerEngagementEvent::CLICK`     |
| `complained`          | `MailerEngagementEvent`   | `MailerEngagementEvent::SPAM`      |
| `unsubscribed`        | `MailerEngagementEvent`   | `MailerEngagementEvent::UNSUBSCRIBE` |
| `test`                | `MailChannels\Plugins\Symfony\RemoteEvent\TestEvent` | `TestEvent::TEST` |

An unrecognized `event` value throws
`Symfony\Component\RemoteEvent\Exception\ParseException`.

`TestEvent` is a MailChannels-specific addition — it is **not** part of
Symfony's core `symfony/remote-event` event set. It's what you'll receive
when triggering a test webhook from the dashboard, or the API
(`$client->webhooks->validate()`). Match on it (or on `default` as above)
rather than assuming every payload is a `MailerDeliveryEvent` or
`MailerEngagementEvent`.

## What The Converter Populates On Each Event

- **`id`** — `"{request_id}_{timestamp}"` if `request_id` is present,
  otherwise just the `timestamp`.
- **`date`** — built from the payload's Unix `timestamp`.
- **`recipientEmail`** — set to the *first* entry in `recipients`, if any.
  If you need the full recipient list, read it off the raw payload (see
  below) rather than `getRecipientEmail()`.
- **`reason`** — set on `MailerDeliveryEvent` when the payload has a
  `reason` field (present on bounce events); leave unset otherwise.
- **Raw payload** — every event class keeps the original decoded payload
  array accessible via `getPayload()`; use it for fields the converter
  doesn't lift into a dedicated getter (`status`, `smtp_id`, `campaign_id`,
  `url`/`user_agent`/`ip` on engagement events — see the field table in
  [webhooks.md](../webhooks.md#event-payloads)).

A single HTTP request can carry a batch of events — `doParse()` returns an
`array<RemoteEvent>` (one per array element in the JSON body), and the
webhook controller dispatches each through Messenger individually.

## Errors And Rejection

Verification failures are raised as
`Symfony\Component\Webhook\Exception\RejectWebhookException` (missing/invalid
headers, stale signature, digest mismatch, unparseable `Signature`/`Signature-Input`,
signature/public-key mismatch) — Symfony turns these into a `406` response
automatically, so you don't need to catch them yourself. A failure to fetch
the public key from the API surfaces as a `500` `RejectWebhookException`
rather than leaking a raw `MailChannelsException`.

Fetched public keys are cached **in-memory on the parser instance** for 300
seconds; since a fresh instance is typically built per-request in Symfony,
this mostly saves repeat lookups within a single batch. Cache invalidation
matters more on the SDK side if you call `$client->webhooks->publicKey()`
yourself elsewhere — see the caching note in
[webhooks.md](../webhooks.md#verify-incoming-webhooks-crucial).

## Limitations

- Only `POST` requests with a JSON content type are matched; anything else
  is rejected before parsing (`406`).
- There's no way to pass per-route options (e.g. a longer key-cache TTL)
  through `webhooks.yaml` — construct the parser with a custom `$client`
  if you need different behavior, or use the manual verification flow in
  [webhooks.md](../webhooks.md#verify-incoming-webhooks-crucial) directly.
