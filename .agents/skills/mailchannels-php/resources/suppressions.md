# Suppressions

A suppression list keeps known-bad or opted-out recipients out of future
sends. MailChannels suppresses by recipient + suppression type + source.

## Create Entries

```php
use MailChannels\Enum\SuppressionSource;

$client->suppressions->create(
    [
        [
            'recipient'         => 'out@example.net',
            'suppression_types' => ['non-transactional'],   // defaults to non-transactional
            'notes'             => 'Imported from preference center', // optional, max 1024 chars
        ],
        [
            'recipient'         => 'complainer@example.net',
            'suppression_types' => ['transactional', 'non-transactional'],
        ],
    ],
    addToSubAccounts: true, // parent only; copies entries to every sub-account
);
```

Constraints:

- **Atomic**: either every entry in the request is added, or none are.
- A single request can carry at most **1000** entries combined across the
  parent and any sub-accounts.
- Each `recipient` is up to 255 characters; `notes` up to 1024.
- `suppression_types` items must be `transactional` or `non-transactional`.
- The endpoint does not have a field to set the source value. All entries created via this endpoint have an inherent source of `api`.

If any entry already exists, the API returns **409 Conflict** and throws
`ConflictException`.

## List Entries

```php
use MailChannels\Enum\SuppressionSource;

$entries = $client->suppressions->list(
    recipient: 'recipient@example.net',   // exact-match filter
    source: SuppressionSource::Api,       // see source enum values below
    createdAfter: '2026-04-01',
    createdBefore: '2026-05-01T00:00:00Z',
    limit: 100,                           // 1..1000, default 1000
    offset: 0,
);
```

`SuppressionSource` enum values:

| Enum | String | Meaning |
| --- | --- | --- |
| `SuppressionSource::Api` | `api` | Explicitly created via the API. |
| `SuppressionSource::UnsubscribeLink` | `unsubscribe_link` | Recipient used the hosted unsubscribe URL. |
| `SuppressionSource::ListUnsubscribe` | `list_unsubscribe` | Recipient used one-click `List-Unsubscribe`. |
| `SuppressionSource::HardBounce` | `hard_bounce` | Bounced as undeliverable. |
| `SuppressionSource::SpamComplaint` | `spam_complaint` | Recipient reported spam. |

Date formats accepted: `YYYY-MM-DD` or `YYYY-MM-DDTHH:MM:SSZ`.

You can also pass the raw string values instead of the enum.

## Delete An Entry

```php
use MailChannels\Enum\SuppressionDeleteSource;

$client->suppressions->delete('recipient@example.net');               // defaults to source=api
$client->suppressions->delete('recipient@example.net', source: SuppressionDeleteSource::All); // removes all sources
$client->suppressions->delete('recipient@example.net', source: SuppressionDeleteSource::HardBounce); // removes only hard_bounce entry
```

`SuppressionDeleteSource` enum values:

| Enum | String | Meaning |
| --- | --- | --- |
| `SuppressionDeleteSource::Api` | `api` | Delete only the API-created entry (default). |
| `SuppressionDeleteSource::UnsubscribeLink` | `unsubscribe_link` | Delete only the unsubscribe-link entry. |
| `SuppressionDeleteSource::ListUnsubscribe` | `list_unsubscribe` | Delete only the list-unsubscribe entry. |
| `SuppressionDeleteSource::HardBounce` | `hard_bounce` | Delete only the hard-bounce entry. |
| `SuppressionDeleteSource::SpamComplaint` | `spam_complaint` | Delete only the spam-complaint entry. |
| `SuppressionDeleteSource::All` | `all` | Delete entries for all sources. |

## Patterns

- **Preference center opt-out**: `create([['recipient' => '...', 'suppression_types' => ['non-transactional'], 'notes' => '...']])`.
- **Migrating from another ESP**: bulk-create with `addToSubAccounts: true` so every tenant
  inherits the list.
- **Re-engagement campaign**: `list(source: SuppressionSource::HardBounce, ...)` and only
  resurrect recipients you've manually verified.
