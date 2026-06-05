<?php

declare(strict_types=1);

/**
 * Sync README.md's "Built and tested against Email API X.Y.Z" line with
 * the version published in the Email API OpenAPI spec.
 *
 * Run via `composer api-version`. CI runs it as part of the freshness
 * checks and then `git diff --exit-code -- README.md` catches drift.
 *
 * Source of truth: `info.version` field at
 * `https://docs.mailchannels.net/email-api.yaml`. Fetched fresh each
 * run; exits non-zero on network failure or unexpected spec shape so
 * the drift check can't silently pass on stale data.
 */

namespace MailChannels\Scripts\UpdateApiVersion;

const SPEC_URL = 'https://docs.mailchannels.net/email-api.yaml';
const README_PATH = __DIR__ . '/../README.md';
const README_PATTERN = '/(Built and tested against Email API )(\S+)/';
const NETWORK_TIMEOUT_SECONDS = 10;

main();

function main(): void
{
    $specVersion = fetchSpecVersion();
    $readme = file_get_contents(README_PATH);
    if ($readme === false) {
        fail('Cannot read ' . README_PATH);
    }

    $count = 0;
    $updated = preg_replace(README_PATTERN, '${1}' . $specVersion, $readme, -1, $count);
    if ($updated === null) {
        fail('preg_replace failed against README.md');
    }
    if ($count === 0) {
        fail("README does not contain the 'Built and tested against Email API X' line.");
    }
    if ($count > 1) {
        fail("README contains the 'Built and tested against Email API X' line more than once ({$count} hits).");
    }

    if ($updated === $readme) {
        fwrite(STDERR, "README already in sync (Email API {$specVersion}).\n");
        return;
    }

    if (!preg_match(README_PATTERN, $readme, $previous)) {
        fail('Unexpected: pattern matched on replace but not on read-back.');
    }

    file_put_contents(README_PATH, $updated);
    fwrite(STDERR, "Updated README: Email API {$previous[2]} -> {$specVersion}\n");
}

function fetchSpecVersion(): string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => NETWORK_TIMEOUT_SECONDS,
            'user_agent' => 'mailchannels-php-sdk/update-api-version',
            'header' => "Accept: application/yaml, text/yaml, */*\r\n",
        ],
    ]);

    $yaml = @file_get_contents(SPEC_URL, false, $context);
    if ($yaml === false) {
        $err = error_get_last();
        fail('Failed to fetch ' . SPEC_URL . ($err !== null ? ': ' . $err['message'] : ''));
    }

    // Walk the YAML manually to find the `info.version` field rather than
    // pulling in a YAML parser dependency. The spec is a few thousand
    // lines and well-formed; a tiny indent-tracking loop is enough.
    $inInfoBlock = false;
    foreach (explode("\n", $yaml) as $line) {
        if (preg_match('/^info\s*:\s*$/', $line)) {
            $inInfoBlock = true;
            continue;
        }
        // A non-indented, non-blank line means a new top-level key — we've
        // left the info block.
        if ($inInfoBlock && $line !== '' && !preg_match('/^\s/', $line)) {
            break;
        }
        if ($inInfoBlock && preg_match('/^\s+version\s*:\s*([^\s#]+)/', $line, $match)) {
            return trim($match[1], "\"'");
        }
    }

    fail("Could not find 'info.version' field in the OpenAPI spec.");
}

function fail(string $message): never
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}
