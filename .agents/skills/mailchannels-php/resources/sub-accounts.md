# Sub-Accounts

Sub-accounts are first-class on MailChannels. Use them for tenants,
customers, or isolated senders so that one customer's reputation, limits,
and bad traffic don't contaminate the parent account or other tenants.

> Sub-accounts are only available on parent accounts on the 100K and higher plans.

## Handles

A handle uniquely identifies a sub-account. Rules:

- 3–128 characters.
- **Lowercase alphanumeric only.** No hyphens, underscores, or uppercase.
- Unique per parent account.
- If omitted on create, a random handle is generated.

`SubAccounts::validateHandle(string $handle)` is public and can be called
to validate a handle before using it.

## Lifecycle

```php
// Create (companyName is required; handle is optional)
$sub = $client->subAccounts->create(companyName: 'Client A', handle: 'clienta');

// List (paginated; default page size is server-determined)
$page = $client->subAccounts->list(limit: 100, offset: 0);

// Suspend / activate
$client->subAccounts->suspend('clienta');
$client->subAccounts->activate('clienta');

// Delete
$client->subAccounts->delete('clienta');
```

`companyName` must be 3–128 characters. `handle` (when provided) must
match the alphanumeric-only pattern above.

## Credentials

Each sub-account has its own API keys and SMTP passwords. They are
accessed via nested resources on `$client->subAccounts`:

```php
// API keys
$created = $client->subAccounts->apiKeys->create('clienta');
// Persist $created — the full key value is returned ONCE; store immediately.
$keys = $client->subAccounts->apiKeys->list('clienta');
$client->subAccounts->apiKeys->delete('clienta', $keyId);

// SMTP passwords
$created = $client->subAccounts->smtpPasswords->create('clienta');
$passwords = $client->subAccounts->smtpPasswords->list('clienta');
$client->subAccounts->smtpPasswords->delete('clienta', $passwordId);
```

**Listed keys and passwords are redacted.** The full secret is only returned
once, at create time. Store it immediately and securely.

## Limits

Per-sub-account monthly send caps. A value of `-1` (returned by GET) means
the sub-account inherits the parent's limit.

```php
$client->subAccounts->limits->set('clienta', sends: 100_000);
$client->subAccounts->limits->retrieve('clienta');
$client->subAccounts->limits->delete('clienta'); // back to inheriting parent
```

`set()` accepts either `sends` or its alias `monthlyLimit`.
The value must be >= 0.

## Usage

```php
$parentUsage = $client->usage->retrieve();
$subUsage    = $client->subAccounts->retrieveUsage('clienta');

echo $parentUsage->totalUsage;          // int, messages sent this period
echo $parentUsage->periodStartDate;     // ISO date string
echo $parentUsage->periodEndDate;
```

## Sending As A Sub-Account

Create a separate `Client` with the sub-account's API key. Don't mix it
into the parent-account client.

```php
use MailChannels\Client;
use MailChannels\Message\{EmailAddress, EmailParams, Personalization, Content};

$tenantClient = new Client(apiKey: 'SUB-ACCOUNT-API-KEY');

$tenantClient->emails->queue(
    new EmailParams(
        from: new EmailAddress('sender@tenant.example'),
        personalizations: [
            new Personalization(to: ['recipient@example.net']),
        ],
        subject: 'From a tenant',
        content: [Content::plain('Hello')],
    )
);
```

This keeps the account boundary explicit in code and avoids hard-to-debug
issues where the wrong key is used at the wrong time.
To share configuration options in multi-tenant code, use `Config::withApiKey()`
to copy the parent config and swap in the tenant's key:

```php
use MailChannels\Client;

$baseConfig   = $client->config();
$tenantConfig = $baseConfig->withApiKey($tenantApiKey);
$tenantClient = Client::fromConfig($tenantConfig);
$tenantClient->emails->queue([...]);
```

## Suppressions And Sub-Accounts

When creating suppressions on the parent, pass `addToSubAccounts: true` to
also copy the entries into every sub-account. See [suppressions](suppressions.md).
