# Unsubscribe

MailChannels offers two **complementary** unsubscribe mechanisms — an
in-body unsubscribe link, and the standard `List-Unsubscribe` /
`List-Unsubscribe-Post` headers. They are not alternatives. A
non-transactional (bulk / marketing / notification) send should use
**both**: the headers let inbox providers surface a one-click
"Unsubscribe" button in their UI, and the in-body link is what
recipients actually see and click inside the message body. Major inbox
providers (Gmail, Yahoo, etc.) effectively require both for bulk senders.

Both mechanisms require the message to have **exactly one recipient per
personalization** and to be **DKIM-signed**.

## In-Body Unsubscribe Link

Use the literal placeholder `{{mc-unsubscribe-url}}` inside mustache HTML
content. MailChannels substitutes a hosted one-click unsubscribe URL at
render time.

```php
use MailChannels\Message\{EmailAddress, EmailParams, Personalization, Content};

$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        subject: 'Newsletter',
        content: [
            Content::mustacheHtml(
                '<p>Hello!</p>' .
                '<p><a href="{{mc-unsubscribe-url}}">Unsubscribe</a></p>'
            ),
        ],
    )
);
```

By itself, the in-body link is fine for occasional one-off sends. For
anything resembling bulk mail, combine it with the headers below.

## `List-Unsubscribe` Headers (Non-Transactional)

Setting `transactional: false` tells MailChannels to add
`List-Unsubscribe` and `List-Unsubscribe-Post` headers automatically.
These headers are what inbox providers read to render their own
"Unsubscribe" button next to the sender's name.

**Always include the in-body link in the same payload** so the message
is fully compliant on both surfaces:

```php
$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        subject: 'Marketing message',
        content: [
            Content::mustacheHtml(
                '<p>Today\'s update…</p>' .
                '<p><a href="{{mc-unsubscribe-url}}">Unsubscribe</a></p>'
            ),
        ],
        transactional: false,
        dkimDomain: 'example.com',
        dkimSelector: 'mcdkim',
    )
);
```

If `transactional: false` is set but the message has more than one
recipient per personalization, MailChannels returns **400 Bad Request**.

## When To Use Which

| Message type | In-body link | `transactional: false` |
| --- | --- | --- |
| Transactional (receipts, password resets, confirmations) | No | No (omit `transactional` or pass `true`). |
| Bulk / marketing / newsletter / notification | **Yes** | **Yes** — combine both in the same payload. |

There is essentially no situation where you'd want headers but not the
in-body link; if you're sending non-transactional mail, you need both.

Both modes require DKIM signing (see the intro). The example above shows
the minimum payload — `dkimDomain` + `dkimSelector`. See
[dkim](dkim.md) for how to create the underlying key.
