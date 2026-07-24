# Laravel Mail Integration

The SDK includes a Laravel service provider that registers `mailchannels`
as a `Mail` driver. It's the same `MailChannelsApiTransport` used by the
[Symfony Mailer integration](symfony-mailer.md) — Laravel's `Mail` facade is
built on Symfony Mailer under the hood, so everything described there about
header mapping and control headers applies here too.

`MailChannels\Plugins\Laravel\MailChannelsServiceProvider` is auto-discovered
via Laravel package auto-discovery (`extra.laravel.providers` in
`composer.json`) — no manual registration needed.

This integration is optional — the SDK only *suggests* `illuminate/mail`
rather than hard-requiring it. Every real Laravel app already has it via
`laravel/framework`, so there's nothing extra to install.

## Setup

Add a `mailchannels` mailer in `config/mail.php`:

```php
$mailConfig = [
    // ...
    'mailers' => [
        // ...
        'mailchannels' => [
            'transport' => 'mailchannels',
        ],
    ],
];
```

Add the API key (and, optionally, a base-URL override) in `config/services.php`:

```php
$servicesConfig = [
    // ...
    'mailchannels' => [
        'api_key'  => env('MAILCHANNELS_API_KEY'),
        'base_url' => env('MAILCHANNELS_API_URL'), // optional; omit for the default
    ],
];
```

Point Laravel at the new mailer:

```bash
MAIL_MAILER=mailchannels
MAILCHANNELS_API_KEY="mc-..."
```

### Config Resolution

`api_key` and `base_url` can also be set directly on the mailer's own config
array in `config/mail.php` (e.g. `'mailers' => ['mailchannels' => ['transport' => 'mailchannels', 'api_key' => '...']]`).
A missing or blank API key from both sources throws `InvalidArgumentException`
when the mailer is first resolved.

A PSR-3 logger is picked up automatically if one is bound to
`Psr\Log\LoggerInterface` in the container; otherwise the transport falls
back to `Psr\Log\NullLogger`.

## Sending Mail

```php
use Illuminate\Support\Facades\Mail;

Mail::to('recipient@example.com')->send(new YourMailable());
```

Anything built with Laravel's `Mailable` (or a raw `Symfony\Component\Mime\Email`)
works normally — `from`, `to`/`cc`/`bcc`, `subject`, `text`/`html` bodies, and
attachments all map the same way as plain Symfony Mailer usage.

## Control Headers

Set `x-mailchannels-*` headers from a `Mailable`'s `headers()` method to reach
fields with no Laravel-level API (async vs sync send, tracking, DKIM,
campaign id, custom domains) — see [symfony-mailer.md](symfony-mailer.md#control-headers-x-mailchannels)
for the full table:

```php
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;

class YourMailable extends Mailable
{
    // ...

    public function headers(): Headers
    {
        return new Headers(
            text: ['x-mailchannels-transactional' => 'true'],
        );
    }
}
```

These headers are stripped before the message is sent — recipients never
see them.

## Errors

`MailChannelsException` subclasses (auth, validation, rate limit, server
errors — see [error-handling](../error-handling.md)) surface as
`Symfony\Component\Mailer\Exception\TransportException`, same as plain
Symfony Mailer usage. Laravel's own mail-queue retry/backoff behavior (for
`ShouldQueue` mailables) applies unchanged on top of that.

## Limitations

Same as the [Symfony Mailer integration](symfony-mailer.md#limitations) for
personalization and DKIM: one personalization per message, DKIM only via
headers (not Laravel's own signing).

Unlike the Symfony DSN factory, this provider never picks up a custom HTTP
client automatically — `Mail::extend()` here only forwards `api_key` and
`base_url` to `MailChannelsApiTransport`, so it always falls back to the
SDK's own `php-http/discovery`. Construct `MailChannelsApiTransport` yourself
and bind it in a custom mailer if you need a specific `httpClient`.
