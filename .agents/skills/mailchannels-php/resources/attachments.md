# Attachments

MailChannels expects every attachment to be Base64-encoded with a `filename`
and ideally a MIME `type`. Use `Attachment::fromBytes()` — it encodes for
you and infers the MIME type from the filename extension.

## From In-Memory Bytes

```php
use MailChannels\Message\Attachment;

$invoice = Attachment::fromBytes(
    (string) file_get_contents('/tmp/invoice.pdf'),
    'invoice.pdf',
    'application/pdf', // optional; inferred from filename when omitted
);

$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('billing@example.com'),
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        subject: 'Your invoice',
        content: [Content::plain('Your invoice is attached.')],
        attachments: [$invoice],
    )
);
```

## From A Local File Path

The SDK doesn't have a `fromFile()` factory, so read the file yourself:

```php
$data = file_get_contents('/path/to/report.csv');
if ($data === false) {
    throw new \RuntimeException('Could not read attachment file');
}

$attachment = Attachment::fromBytes($data, 'report.csv', 'text/csv');
```

## Inline Images (CID References)

For images that should render inside the HTML body rather than as
downloadable attachments, set a `contentId`, the disposition will automatically
be set to `inline`

```php
$logo = Attachment::fromBytes(
    (string) file_get_contents('/path/to/logo.png'),
    'logo.png',
    'image/png',
    contentId: 'company-logo',
);

$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        subject: 'Inline image',
        content: [
            Content::html('<img src="cid:company-logo" alt="Company logo">'),
        ],
        attachments: [$logo],
    )
);
```

## Automatic MIME Type Inference

When `contentType` is omitted from `Attachment::fromBytes()`, the SDK
infers the type from the filename extension. Common extensions are
covered: `pdf`, `txt`, `csv`, `html`, `json`, `xml`, `png`, `jpg`,
`jpeg`, `gif`, `svg`, `webp`, `zip`, `gz`, `doc`, `docx`, `xls`, `xlsx`,
`ppt`, `pptx`, `ics`, `eml`. Unknown extensions fall back to
`application/octet-stream`.

## Raw Attachment Shape (Array Form)

In the array payload form, attachments follow the same schema:

```php
$client->emails->queue([
    'from'    => 'sender@example.com',
    'to'      => 'recipient@example.net',
    'subject' => 'Invoice',
    'text'    => 'See attached.',
    'attachments' => [
        [
            'content'  => base64_encode($pdfBytes),
            'filename' => 'invoice.pdf',
            'type'     => 'application/pdf',
        ],
    ],
]);
```

`content` and `filename` are required. `type` is recommended but optional.

## Limits

- Up to **1000 attachments** per send.
- Combined message + headers + attachments must stay under **30 MB**.
- Exceeding the size limit raises `PayloadTooLargeException` (HTTP 413).
