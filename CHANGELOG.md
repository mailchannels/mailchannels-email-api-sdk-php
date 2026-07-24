# Changelog

All notable changes to this project are documented in this file. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Reserved for in-progress work targeting the next release.

## [1.4.0] - 2026-07-24

### Added
- Symfony transport plugin `MailChannels\Plugins\Symfony\Transport\MailChannelsTransportFactory` for
  `symfony/mailer`. It wraps the SDK to provide a `MailChannelsTransport` that can be used in Symfony's
  `Mailer` component. Supports DSN `mailchannels+api://API_KEY@default`
- Laravel service provider plugin `MailChannels\Plugins\Laravel\MailChannelsServiceProvider` for `laravel/framework`.
  It registers the Symfony transport plugin and provides a `mailchannels` mailer driver.

## [1.3.0] - 2026-07-15

### Added
- `UsageResponse` (shared by `Usage::retrieve()` and
  `SubAccounts::retrieveUsage()`) gains `$monthlyLimit` — the effective
  send cap for the current billing period. A value of `0` means the
  account cannot send; on a sub-account with no explicit limit set, this
  reports the parent account's limit rather than `-1` (contrast
  `SubAccountLimits::retrieve()`, which does return `-1` in that case).
  Typed `?int` even though the spec marks it required, matching this
  SDK's existing convention of keeping new response fields nullable so
  they can never break a caller's mocked test fixtures.

## [1.2.0] - 2026-07-03

### Added
- **Custom Tracking Domains.** A new `CustomTrackingDomains` resource
  (`list()`, `create()`, `update()`, `delete()`) wraps the
  `/custom-tracking-domains` endpoints, letting you register a branded
  hostname for click tracking, open tracking, or unsubscribe links instead
  of MailChannels' shared domains. Registration requires a CNAME and, on
  first use, a TXT ownership-verification record; `create()`/`update()`
  return a `CustomTrackingDomainResponse` covering both the registered and
  pending-DNS-verification shapes (mirroring `SendResponse`'s precedent for
  one endpoint with two success shapes) since MailChannels returns either
  with a 2xx status. New `CustomTrackingScope` (`Click`/`Open`/`Unsubscribe`)
  and `CustomTrackingStatus` (`Active`/`Disabled`) enums.
- `tracking_settings` entries (`open_tracking`/`click_tracking`) already
  passed a `custom_domain_name` key through opaquely, since
  `tracking_settings` is a plain associative array on both the
  array-payload and `EmailParams` typed-object paths — no dedicated value
  class, unlike `Personalization`/`Content`. This release adds the matching
  `unsubscribe_settings` plumbing (same opaque-array shape, both paths) so
  `custom_domain_name` can also route unsubscribe links through a
  registered custom domain.

## [1.1.0] - 2026-07-03

### Added
- `MetricsEngagementResponse` gains `$uniqueOpen`, `$uniqueClick`,
  `$uniqueOpenTrackingDelivered`, `$uniqueClickTrackingDelivered`, and their
  matching `*Buckets` breakdowns. Unlike `$open`/`$click`, these count each
  message at most once regardless of how many times it was opened or
  clicked, so they're the correct numerator/denominator for open and click
  rates that can't exceed 100%.
- `MetricsPerformanceResponse` gains `$complained` and `$complainedBuckets`
  — a count of messages that triggered a spam complaint, ahead of its
  rollout on the live Email API spec.

## [1.0.2] - 2026-06-25

### Fixed
- URL-encode path variables across DKIM, metrics, sub-account,
  suppression, and webhook batch endpoints.
- Prevent reserved characters in path values from changing URL path
  structure or leaking into query strings.

## [1.0.1] - 2026-06-19

- Added a new AI agent Skill under .agents/skills/mailchannels-php/ to guide AI agents using this sdk.
  It provides a summary of the API surface, examples, and instructions that might not be obvious from
  the public API alone.
- README "Using with an AI agent" section added to highlight the new skill and provide instructions
  for its installation and usage.

## [1.0.0] - 2026-06-05

### Changed
- Promoted to **stable**. The public API surface is now under SemVer
  guarantees — breaking changes will only happen on major version
  bumps. No code changes from `0.4.0`; this release is the
  declaration of stability and aligns the SDK with the Email API
  reaching `1.0.0` itself.
- README "Built and tested against Email API" reference updated from
  `0.21.1` to `1.0.0`.

## [0.4.0] - 2026-06-05

### Fixed
- `Resource\Webhooks::resendBatch()` now authenticates via the standard
  `X-Api-Key` header. The previous version sent `X-Customer-Handle` and
  suppressed the API key — that was a misreading of the spec; the
  endpoint accepts the same auth as every other endpoint. **Breaking:**
  the `$customerHandle` parameter is removed from the method signature.

### Added
- README badges for Bitbucket Pipelines build status, the current
  Packagist version, and the PHPStan level. Quick visual signal of
  release health on the package landing page.

## [0.3.0] - 2026-06-03

### Added
- Typed responses across the SDK. Every modeled endpoint that returns a
  body now returns a per-endpoint subclass of `MailChannels\Response`,
  exposing typed read-only properties for each documented field.
  Inherited array-access, iteration, and HTTP-metadata helpers continue
  to work on the typed objects. Classes added:
  - `Emails::send()` → `SendResponse` (with `list<SendResult>|null
    $results` and `list<string>|null $dryRunData`).
  - `Emails::queue()` → `QueuedSendResponse` (`?string $queuedAt`).
  - `Usage::retrieve()` → `UsageResponse`.
  - `CheckDomain::check()` → `CheckDomainResponse`.
  - `Suppressions::list()` / `::create()` → `SuppressionListResponse`
    (with `list<SuppressionEntry>|null`).
  - `Dkim::create()` / `::list()` / `::rotate()` → `DkimKeyResponse`,
    `DkimKeyListResponse`, `DkimRotateResponse` (with `DkimKeyInfo`
    and `DkimDnsRecord` value objects).
  - `Metrics::*` → `MetricsEngagementResponse`,
    `MetricsPerformanceResponse`, `MetricsRecipientBehaviourResponse`,
    `MetricsVolumeResponse`, `MetricsSenderResponse` (with
    `MetricsBucket` and `MetricsSender` value objects).
  - `Webhooks::*` → `WebhookListResponse`, `WebhookBatchListResponse`,
    `WebhookResendResponse`, `WebhookPublicKeyResponse`,
    `WebhookValidationResultsResponse` (with `WebhookSubscription`,
    `WebhookBatch`, `WebhookBatchDuration`, `WebhookEndpointResponse`,
    `WebhookValidationResult` value objects).
  - `SubAccounts::*` and nested resources → `SubAccountResponse`,
    `SubAccountListResponse`, `SubAccountApiKeyResponse`,
    `SubAccountApiKeyListResponse`, `SubAccountSmtpPasswordResponse`,
    `SubAccountSmtpPasswordListResponse`, `SubAccountLimitResponse`
    (with `SubAccountDetails`, `SubAccountApiKey`,
    `SubAccountSmtpPassword` value objects).
- `MailChannels\Exception\ResponseValidationException` raised when a
  2xx response body has a field of the wrong type. Missing fields are
  nulled, not errors — strictness fires only on type mismatches, so the
  SDK stays forward-compatible with future API additions.
- `MailChannels\Internal\ResponseValidator` (marked `@internal`) backs
  the typed-response field extraction with helpers for nullable
  strings/ints/bools, lists of strings, lists of arrays, and assoc
  arrays.

### Changed
- `MailChannels\Response` is no longer `final` so per-endpoint typed
  responses can extend it. The `requestId()` method is overridden on
  several typed subclasses to prefer the response body's `request_id`
  field over HTTP headers when present (e.g. `SendResponse`,
  `QueuedSendResponse`).
- Typed responses are constructed via `new TypedResponse($response)`
  rather than a static factory. The factory form was an early-rollout
  convention; the constructor form is shorter at every call site and
  drops one concept from the docs.
- `Attachment::fromUrl()` adds a remote-URL constructor mirroring the
  Python SDK's helper. Falls back through caller-supplied content
  type → server `Content-Type` → filename extension.

### Fixed
- `SendResponse` now matches the OpenAPI spec. The previous shape
  modeled a top-level `$messageId` field that does not exist on the
  wire — `/send` returns `{request_id, results: [SendResult]}`, with
  per-personalization message ids inside `$results[i]->messageId`.
  `$dryRunData` is now correctly typed `list<string>|null` (one
  rendered RFC822 message blob per personalization), not list of
  arrays.
  **Breaking:** the `$messageId` property is removed from
  `SendResponse`. Callers should read
  `$response->results[0]?->messageId`. The dry-run shape change is
  also breaking for any caller that was iterating
  `$response->dryRunData` as arrays.
- The `mailchannels/mailchannels-php` package can now be installed
  via Composer; 0.2.0 referenced an internal class
  (`MailChannels\Response\ResponseValidator`) that didn't exist after
  the validator moved into `MailChannels\Internal`, so any call into
  a typed response other than `SendResponse`/`QueuedSendResponse`
  fatal-errored at runtime. All typed responses now resolve the
  validator correctly.

## [0.2.0] - 2026-05-29

### Added
- Bitbucket Pipelines CI on a PHP 8.1–8.4 matrix with an 85% line-coverage
  gate enforced via `scripts/check-coverage.php` against PHPUnit's Clover
  output. CI image lives in
  `../build/images/Dockerfile-public-pipeline-mc-email-api-sdk-php` and
  bundles all four PHP versions plus `pcov`.
- `docs/ADVANCED.md` — version/user-agent, `Client`/`Config` reuse,
  multi-tenant patterns, custom PSR-18 transports, the multi-PSR-18
  discovery warning, PSR-3 logger injection, exception hierarchy, and
  low-level Webhook `Verifier` helpers.
- `docs/DKIM.md` — hosted DKIM lifecycle, customer-managed keys, and a
  Cloudflare TXT publication recipe.
- `docs/API_REFERENCE.md` auto-generated by
  `scripts/generate-api-reference.php`. The CI `&checks` step runs the
  generator and `git diff --exit-code` to catch stale references.
- Thirteen new examples under `examples/` (check-domain, cloudflare-dkim,
  custom-headers, custom-http-client, dkim lifecycle, error-handling,
  metrics, queue-email, suppressions, templates, unsubscribe, usage,
  webhooks management) for parity with the Python SDK example set.
- Substantial unit-test additions covering `Resource\Emails` edge cases,
  `Transport` HTTP status mapping and query serialization, and a new
  `tests/Unit/Message/` suite for the typed value objects. New
  `MockHttpClient::lastJson()` test fixture.
- New `composer` scripts: `test:coverage`, `check-coverage`,
  `api-reference`, and an extended `ci` that bundles all checks plus
  the API-reference freshness diff.

### Fixed
- `scripts/check-coverage.php` regex now matches Clover's project-level
  aggregate `<metrics files="N" ...>` element by attribute rather than
  position, so it works against real PHPUnit output (PHPUnit places the
  aggregate at the end of `<project>` after all `<file>` children).

## [0.1.0] - unreleased

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
