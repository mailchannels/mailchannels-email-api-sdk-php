# Symfony Mailer Integration

The SDK ships a Symfony Mailer transport so existing code that builds
`Symfony\Component\Mime\Email` objects and sends them through
`MailerInterface` can reach the MailChannels API without calling `$client`
directly. This integration is optional — it requires `symfony/mailer`,
which the SDK only *suggests* rather than hard-requires.
Most Symfony apps already have it (it's needed to use `MailerInterface` at
all); if not, `composer require symfony/mailer`.

## Setup

Register the factory as a tagged service, e.g. in `config/services.yaml`:

```yaml
services:
    MailChannels\Plugins\Symfony\Transport\MailChannelsTransportFactory:
        tags: ['mailer.transport_factory']
```

Point the mailer DSN at it:

```bash
MAILER_DSN=mailchannels+api://${MAILCHANNELS_API_KEY}@default
```

- Scheme: `mailchannels` or `mailchannels+api` (both accepted).
- DSN user → the API key.
- DSN host, if not the literal `default`, becomes a base-URL override —
  `mailchannels+api://KEY@sandbox.example.com:8443` resolves to
  `https://sandbox.example.com:8443`. Leave it as `default` for production.

The factory picks up the container's autowired PSR-3 logger, Symfony event
dispatcher, and Symfony `HttpClientInterface` automatically — no manual wiring
needed. If the app has its own `HttpClientInterface` configured
(e.g. `framework.http_client` with a proxy or custom timeouts), the DSN-built
transport reuses it via a PSR-18 bridge instead of auto-discovering an HTTP
client.

## Sending

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class YourService
{
    public function __construct(private readonly MailerInterface $mailer) {}

    public function send(): void
    {
        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.net')
            ->subject('Hello')
            ->text('Plain text body.')
            ->html('<p>HTML body.</p>');

        $this->mailer->send($email);
    }
}
```

## How `Email` Maps To `EmailParams`

The transport converts the Symfony `Email` into a single `MailChannels\Message\EmailParams`
call under the hood — construction and the HTTP request itself are entirely
delegated to `$client->emails`:

| `Email` | `EmailParams` |
| --- | --- |
| `from()` (falls back to the envelope sender) | `from` |
| `to()` / `cc()` / `bcc()` (envelope recipients, minus any already in cc/bcc) | one `Personalization` — see [sending](../sending.md) for what a personalization is |
| `replyTo()` | `replyTo` — **only the first address is used** even if several are set |
| `subject()` | `subject` |
| `text()` / `html()` | `content` (`Content::plain` / `Content::html`) |
| `attach()`, `attachFromPath()`, `addPart(DataPart)` | `attachments` |
| any non-structural header | `headers` (see below) |

Only one `Personalization` is ever built per `Email` — this transport does
not expose the SDK's multi-recipient fan-out. Call `$client->emails` directly
(see [sending](../sending.md)) if you need several personalizations in one
request.

## Control Headers (`x-mailchannels-*`)

Set these on the `Email`'s header bag to reach fields that don't have a
first-class Symfony `Email` setter. They are stripped before the message is
sent — recipients never see them:

```php
$email = new Email();
$email->getHeaders()->addTextHeader('x-mailchannels-transactional', 'true');
```

| Header                                  | `EmailParams` field |
|-----------------------------------------|------------|
| `x-mailchannels-send-async`             | Selects `queue()` (`true`, default) vs `send()` (`false`) |
| `x-mailchannels-click-tracking-enable` | `trackingSettings['click_tracking']['enable']` |
| `x-mailchannels-click-tracking-domain`  | `trackingSettings['click_tracking']['custom_domain_name']` |
| `x-mailchannels-open-tracking-enable`  | `trackingSettings['open_tracking']['enable']` |
| `x-mailchannels-open-tracking-domain`   | `trackingSettings['open_tracking']['custom_domain_name']` |
| `x-mailchannels-unsubscribe-domain`     | `unsubscribeSettings['custom_domain_name']`          |
| `x-mailchannels-transactional`          | `transactional` |
| `x-mailchannels-dkim-domain`            | `dkimDomain`   |
| `x-mailchannels-dkim-selector`          | `dkimSelector`    |
| `x-mailchannels-dkim-private-key`       | `dkimPrivateKey`       |
| `x-mailchannels-campaign-id`            | `campaignId`  |

Refer back to the original SKILL.md for links to full explanations of each field.

Boolean headers accept only the literal strings `"true"` / `"false"`
Anything else throws `Symfony\Component\Mailer\Exception\TransportException`.
An empty header value is treated as unset (falls back to the default).

Any other custom header (e.g. `X-Other-Header`) passes through as-is into
`headers`. Headers that Symfony's `Email` already represents structurally
(`From`, `To`, `Cc`, `Bcc`, `Subject`, `Reply-To`, `Date`, `Message-ID`,
`Content-Type`, `Content-Transfer-Encoding`, `MIME-Version`) are dropped
rather than forwarded — they'd otherwise collide with the structured fields
the API derives them from. If the same custom header is added twice, the
**last** value wins.

`DKIM-Signature`, `Authentication-Results`, and `Received` are *not*
structural — Symfony doesn't generate these on its own, so if they're present
it means something (e.g. Symfony's own `Crypto\DkimSigner`, or a forwarded
message) added them deliberately. Forwarding them as opaque custom headers
would be misleading — most notably, it would let a message through looking
DKIM-signed when MailChannels never saw that signature. The transport instead
rejects the send with a `TransportException` if any of these three are
present; strip them from the `Email` yourself first if you have a reason to
send anyway.

## Reading The Result

- `send()` (i.e. `x-mailchannels-send-async: false`): the first result's
  `messageId` is written back onto Symfony's `SentMessage` via
  `setMessageId()`.
- `queue()` (default): the API doesn't return a message id per call, so
  Symfony's own generated message id is left untouched.

## Errors

Any `MailChannels\Exception\MailChannelsException` raised by the SDK (auth,
validation, rate limit, server errors — see [error-handling](../error-handling.md))
is caught and re-thrown as `Symfony\Component\Mailer\Exception\TransportException`
with the original message appended, so standard Symfony error handling and
retry logic around `MailerInterface::send()` keeps working unchanged.

## Limitations

- One personalization per `Email` — no per-recipient template data or
  per-recipient header overrides through this transport.
- DKIM is only reachable via the `x-mailchannels-dkim-*` headers above;
  Symfony's own `Crypto\DkimSigner` middleware is not wired into this
  transport (MailChannels signs on its own side), and a message signed with
  it will fail to send — see the note on `DKIM-Signature` above.
  For more information on managing MailChannels DKIM, see [dkim.md](../dkim.md).
- The DSN-built transport picks up the app's own Symfony `HttpClientInterface`
  automatically (see above), but there's no DSN option to point it at a
  *different* HTTP client than the rest of the app uses. Build a
  `MailChannelsApiTransport` directly (skip the factory) if you need a
  specific `httpClient`, `requestFactory`, or `streamFactory`.
