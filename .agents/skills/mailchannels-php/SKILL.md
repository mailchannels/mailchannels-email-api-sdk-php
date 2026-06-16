---
name: mailchannels-php
description: PHP SDK for the MailChannels Email API (Packagist `mailchannels/mailchannels-php`; namespace `MailChannels\`). Use when sending or queueing email, working with attachments, mustache templates, unsubscribe, custom headers, DKIM keys, sub-accounts, domain checks, metrics, usage, suppressions, or webhooks (incl. signature verification); also configuring clients & custom PSR-18 HTTP transports, PSR-3 logging, and error handling. Skip when (a) developing the SDK itself — defer to the repo's root AGENTS.md; (b) the user is in a non-PHP language — MailChannels publishes separate SDKs for other ecosystems and the PHP types/idioms here don't apply.
---

# MailChannels PHP SDK

**Package:** `mailchannels/mailchannels-php` (Packagist) | **Namespace:** `MailChannels\` | **PHP:** ≥ 8.1

This skill covers *consuming* the SDK from application code. Do not apply it to non-PHP contexts (different SDK, different types/idioms) or to development work on the SDK itself (see repo root `CONTRIBUTING.md`).

## How To Use This Skill

The body of each topic lives in [`resources/`](resources/). Load only the resource files that match the task — don't preload everything.

### Decision Tree

Start at the top; descend until you hit a leaf, then read the linked
resource. If multiple branches apply (e.g. "send + attachments +
templates"), read each leaf.

```
You're about to write PHP SDK code. What does it need to do?

├── Get oriented — what is the SDK, what are the entry points?
│   → resources/overview.md
│
├── Set up the client (API keys, base URL, multi-tenant, PSR-18 HTTP
│   transport, PSR-3 logging)
│   → resources/clients-and-transport.md
│
├── Send email
│   → resources/sending.md
│   ├── …with file / bytes / inline-image attachments?
│   │   → resources/attachments.md
│   ├── …with a mustache template + per-recipient data?
│   │   → resources/templates.md
│   ├── …with an unsubscribe link or List-Unsubscribe header?
│   │   → resources/unsubscribe.md
│   └── …with custom X-… headers?
│       → resources/custom-headers.md
│
├── Manage hosted DKIM keys (create / list / rotate / revoke)
│   → resources/dkim.md
│
├── Validate a sender domain's auth posture (DKIM/SPF/Lockdown/DNS)
│   → resources/domain-checks.md
│
├── Multi-tenant work — sub-accounts, their credentials, limits, usage
│   → resources/sub-accounts.md
│
├── Query analytics (volume, engagement, performance, senders) or
│   current-period usage
│   → resources/metrics-and-usage.md
│
├── Manage the suppression list (list, create, delete)
│   → resources/suppressions.md
│
├── Receive or manage delivery-event webhooks (enroll, validate,
│   inspect batches, verify signatures)
│   → resources/webhooks.md
│
├── Catch / log / retry SDK or API errors
│   → resources/error-handling.md
│
└── Read typed response objects
    → resources/responses.md
```

## Code Conventions

- **PHP 8.1+**: use named arguments, enums, readonly properties, and union types throughout.
- Prefer the **typed object API** (`EmailParams`, `EmailAddress`, `Personalization`, `Content`, `Attachment`) over plain arrays — objects validate at construction and are statically checked by PHPStan.
- Example addresses use IANA-reserved `example.com` / `example.net` — replace before running.

## Authoritative Source

Class signatures and docblocks under `src/` in the installed package are the ground truth when a resource is ambiguous.
