# PHP SDK Overview

The PHP `mailchannels/mailchannels-php` package (Packagist:
`mailchannels/mailchannels-php`) exposes resources as readonly properties on
an explicit `Client` instance. There is no module-level singleton — always
create a `Client`:

```php
use MailChannels\Client;

// Preferred: set MAILCHANNELS_API_KEY in the environment. The SDK reads it
// automatically — no PHP-side configuration needed.
//   export MAILCHANNELS_API_KEY="..."
$client = new Client();
$client->emails->queue([...]);

// Only when env vars aren't possible: pass the key directly (load it from
// an env var or secret store; never hardcode it in source).
$client = new Client(apiKey: getenv('MAILCHANNELS_API_KEY') ?: throw new \RuntimeException('Missing API key'));
```

Use a single `Client` instance per credential for the life of the
request or process — the underlying PSR-18 transport pools connections.
Use a separate `Client` per tenant when you have multiple API keys.

You almost never need to change the base URL — the default
(`https://api.mailchannels.net/tx/v1`) is what production traffic uses.
Only override it if MailChannels has given you a specific alternate
endpoint (e.g. a sandbox or regional URL); otherwise leave it alone.

## Key Concept: Personalizations

A single `Emails::send()` or `Emails::queue()` call can produce **many
individually-rendered messages**. Each entry in the `personalizations`
list is one rendered, addressed message: its own `to` / `cc` / `bcc`,
optional per-recipient template variables (`dynamicTemplateData`),
optional per-recipient header overrides, and so on. The top-level
`from` / `subject` / `content` act as defaults that each personalization
can override.

A "personalization" is therefore not just "a recipient" — it is one
fully-resolved outgoing message. Several resources refer back to this
shape (templates, unsubscribe, custom headers, sub-account sends, etc.).

## Resource Inventory

| Client property | Class | What it does |
| --- | --- | --- |
| `$client->emails` | `Emails` | `send()` and `queue()` — synchronous-send and queued-send. |
| `$client->checkDomain` | `CheckDomain` | DKIM / SPF / sender-domain / Domain Lockdown check. |
| `$client->dkim` | `Dkim` | Create, list, update status, rotate hosted DKIM keys. |
| `$client->subAccounts` | `SubAccounts` | Manage sub-accounts (multi-tenant). |
| `$client->subAccounts->apiKeys` | `SubAccountApiKeys` | Manage sub-account API keys. |
| `$client->subAccounts->smtpPasswords` | `SubAccountSmtpPasswords` | Manage sub-account SMTP passwords. |
| `$client->subAccounts->limits` | `SubAccountLimits` | Set, get, delete per-sub-account send limits. |
| `$client->metrics` | `Metrics` | Volume, engagement, performance, recipient-behaviour, sender metrics. |
| `$client->usage` | `Usage` | Parent-account usage for the current billing period. |
| `$client->suppressions` | `Suppressions` | List, create, delete suppression entries. |
| `$client->webhooks` | `Webhooks` | Enroll, validate, inspect batches, fetch public keys. |

Inbound webhook signature verification uses the stateless
`MailChannels\Webhook\Verifier` class directly — it does not need a
`Client` instance.

## `send` vs `queue`

Two methods exist on `$client->emails`:

| Method | Server behavior | Response type |
| --- | --- | --- |
| `send()` | Regular send path; full per-recipient processing in the request. Returns per-personalization results. | `SendResponse` |
| `queue()` | Queued send; per-recipient work runs in the background and the call returns faster. | `QueuedSendResponse` |

`send` vs `queue` is a **server-side** distinction. `queue()` returns
faster because per-recipient work is deferred. The PHP SDK has no
async variants — both methods make a synchronous HTTP call. For
client-side parallelism, use your PSR-18 client's concurrency features
(e.g. Guzzle's `pool()`).

Default to `queue()` for most production paths.

## Configuration Sources

**The recommended setup is to set `MAILCHANNELS_API_KEY` in the
environment and write no configuration code at all.** The SDK reads it
automatically when `Client` is constructed, and your code stays free of
credentials.

When you do need to override that — multiple tenants in one process, a
test harness, etc. — the SDK looks for each setting in this priority
order:

1. Explicit `new Client(apiKey: '...')`.
2. The `MAILCHANNELS_API_KEY` environment variable.

The first non-empty value wins. The SDK sends the resolved key as the
`X-Api-Key` header on every request.

For the base URL:

1. Explicit `new Client(baseUrl: '...')`.
2. The `MAILCHANNELS_API_URL` environment variable.

The default base URL is `https://api.mailchannels.net/tx/v1` and almost
no application should change it.

## Responses

Every endpoint returns a typed response object (e.g. `QueuedSendResponse`,
`SendResponse`, `CheckDomainResponse`). All response objects extend
`MailChannels\Response` which provides array access, `statusCode`,
`headers`, `rawBody`, and `requestId()`. Typed subclasses add
endpoint-specific readonly properties. See [responses](responses.md).
