# Domain Checks

Use `$client->checkDomain->check()` to confirm a sender domain is properly
authenticated before sending from it. The check covers four things:

- **DKIM** — does the domain publish a DKIM public key matching the
  selectors MailChannels signs with?
- **SPF** — is MailChannels in the domain's SPF record?
- **Sender-domain DNS** — does the domain have at least one of an `A` or
  `MX` record? Receivers reject mail without either as **SDNF**
  ("Sender Domain Not Found"); the check catches this before sends start
  bouncing.
- **Domain Lockdown** — a MailChannels feature that ties a sending domain
  to your account so other MailChannels customers cannot spoof it.

## Basic Check

The checkResults output is structured as a deeply nested array, not as an object with fixed keys.
If strongly typed results are required, implement a custom parser on top of the raw output.

```php
$result = $client->checkDomain->check('example.com');

$checks = $result->checkResults; // array<string, mixed>|null
if ($checks !== null) {
    /** @var array<string, string> $spf */
    $spf = $checks['spf'] ?? [];
    echo $spf['verdict'] . "\n";             // passed | failed | …

    /** @var array<string, string> $lockdown */
    $lockdown = $checks['domain_lockdown'] ?? [];
    echo $lockdown['verdict'] . "\n"; // passed | failed

    /** @var array<string, string> $senderDomain */
    $senderDomain = $checks['sender_domain'] ?? [];
    echo $senderDomain['verdict'] . "\n";   // passed | failed

    /** @var list<array<string, string>> $dkimChecks */
    $dkimChecks = $checks['dkim'] ?? [];
    foreach ($dkimChecks as $dkim) {
        echo $dkim['dkim_selector'] . ': ' . $dkim['verdict'] . "\n";
    }
}

$result->references; // support links if anything failed
```

## With Specific DKIM Settings

If you don't pass any `dkimSettings`, MailChannels uses all stored keys for
the domain. To target a specific selector:

```php
$result = $client->checkDomain->check(
    'example.com',
    dkimSettings: [
        [
            'domain'   => 'example.com',
            'selector' => 'mcdkim',
        ],
    ],
);
```

You can pass up to **10** DKIM settings per call.

### DKIM Settings Resolution Rules

| Provided fields | Behavior |
| --- | --- |
| `domain`, `selector`, `private_key` all present | Verify with the provided key. |
| `domain`, `selector` | Use the stored private key for that domain + selector. |
| `domain` only | Use **all** stored keys for that domain. |
| `selector` only | Use the `domain` from the request body. |
| `private_key` set | `selector` is required too. |
| `dkim_settings` empty / absent | Use all stored keys for the request `domain`. |

## With A Sender ID (Domain Lockdown)

```php
$result = $client->checkDomain->check(
    'example.com',
    senderId: 'example|domain|example.com',
);
```

Pass `senderId` only when your lockdown record uses `senderid=` or
`sidw=` fields. If it uses `auth=` (account-wide authorization), omit
`senderId`.

## Verdicts Reference

DKIM, Domain Lockdown, A, and MX verdicts are each `passed` or `failed`.

SPF has a richer set: `passed`, `failed`, `soft failed`, `temporary error`,
`permanent error`, `neutral`, `none`, or `unknown`.

`senderDomain` overall passes if **either** the A or MX check passes.

## Domain Lockdown

Domain Lockdown ties a sending domain to your MailChannels account so
other customers can't spoof from it. Setup is a single DNS TXT record at
`_mailchannels.<domain>` whose value is a space-separated field list
beginning with `v=mc1`. Three field types are accepted:

| Field | Effect |
| --- | --- |
| `auth=<account-id>` | Authorizes an entire MailChannels account. |
| `senderid=<account>\|<type>\|<sender>` | Authorizes one exact sender identity. |
| `sidw=<account>\|<type>\|<pattern>` | Same as `senderid`, but the pattern may contain `*` wildcards. |

## When To Use

- During onboarding for every new sending domain.
- In CI after rotating DKIM keys, to verify the new DNS record propagated.
- In a periodic monitoring job (the verdicts can drift if DNS changes).
