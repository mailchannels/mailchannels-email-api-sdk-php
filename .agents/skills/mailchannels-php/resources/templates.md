# Templates

MailChannels templates are **send-payload fields**, not a template CRUD
resource. There is no "create template" endpoint. To use a template:

1. Use `Content::mustacheHtml()` (or set `templateType: 'mustache'` on a
   `Content` object) on each templated content item.
2. Provide variables in `dynamicTemplateData` on each personalization.

The only supported template type is `mustache`.

## Multiple Recipients With Per-Recipient Variables

Each personalization renders independently with its own variables:

```php
$client->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@example.com'),
        subject: 'Hi {{name}}',
        personalizations: [
            new Personalization(
                to: ['jane@example.net'],
                dynamicTemplateData: ['name' => 'Jane', 'plan' => 'Pro'],
            ),
            new Personalization(
                to: ['alex@example.net'],
                dynamicTemplateData: ['name' => 'Alex', 'plan' => 'Free'],
            ),
        ],
        content: [
            new Content(
                type: 'text/plain',
                value: 'Hi {{name}}, you\'re on the {{plan}} plan.',
                templateType: 'mustache',
            ),
        ],
    )
);
```

## Allowed `dynamicTemplateData` Value Types

Keys are strings. Values may be:

- string
- bool
- int / float
- list of any of the above
- associative array with string keys and values of any of the above (nested)

## Preview Without Sending

Use `dryRun: true` on `send()` to render and validate without delivering:

```php
$preview = $client->emails->send($params, dryRun: true);

foreach ($preview->dryRunData ?? [] as $rendered) {
    echo $rendered, "\n"; // full RFC822 rendered message per personalization
}
```

Dry-run is the right way to confirm that a template renders correctly before
shipping a new template into production.

## Subject Templates

The top-level `subject` field is **also** mustache-rendered when content has
`templateType: 'mustache'`. There is no separate subject template type.

## Using `Content` Factory Methods

The three `Content` factory methods for templated mail:

| Method | `type` | `templateType` |
| --- | --- | --- |
| `Content::plain(string $value)` | `text/plain` | `null` |
| `Content::html(string $value)` | `text/html` | `null` |
| `Content::mustacheHtml(string $value)` | `text/html` | `mustache` |

For a mustache plain-text part, use `new Content(type: 'text/plain', value: $value, templateType: 'mustache')`.
