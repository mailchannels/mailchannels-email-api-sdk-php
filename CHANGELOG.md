# Changelog

All notable changes to this project are documented in this file. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of the MailChannels PHP SDK.
- Full coverage of MailChannels Email API 0.21.1 (base path `/tx/v1`):
  Emails (`send`, `queue`), SubAccounts (with nested ApiKeys, SmtpPasswords,
  Limits), Metrics, CheckDomain, DKIM, Suppressions, Webhooks, Usage.
- Typed value objects (`EmailAddress`, `EmailParams`, `Personalization`,
  `Content`, `Attachment`) with construction-time validation.
- Typed exception hierarchy under `MailChannels\Exception` covering all
  documented HTTP failure modes; metadata helpers
  (`getStatusCode`, `getRequestId`, `getRetryAfter`, `getSuggestedAction`).
- RFC 9421 webhook helpers in `MailChannels\Webhook\Verifier`:
  parses Signature-Input, verifies Content-Digest, checks signature
  freshness, and reconstructs the canonical signature base for Ed25519
  verification with libsodium.
- `Client::fromConfig()` named constructor for callers that build a
  `Config` upstream (e.g. from a DI container).
- PSR-3 warning when more than one well-known PSR-18 client is installed and
  discovery has to pick one.
- Examples for sending mail, sending with attachments, creating
  sub-accounts, and verifying inbound webhooks.

### Standards conformance
- PSR-1, PSR-3, PSR-7, PSR-17, PSR-18.
- PHP 8.1+ (readonly properties, enums, named arguments).
