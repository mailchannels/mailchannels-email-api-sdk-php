<?php

declare(strict_types=1);

/**
 * Worked example of verifying an inbound MailChannels webhook delivery.
 *
 * The SDK provides RFC 9421 helpers (parse Signature-Input, verify
 * Content-Digest, check freshness, build the canonical signature base). The
 * final Ed25519 verification is done with libsodium, which ships with PHP
 * 7.2+ and so is available in every supported environment without an extra
 * Composer dependency.
 *
 * Drop this behind your web server's POST handler and point a MailChannels
 * webhook subscription at it.
 */

require __DIR__ . '/../vendor/autoload.php';

use MailChannels\Client;
use MailChannels\Webhook\Verifier;

$body = file_get_contents('php://input');
if ($body === false) {
    http_response_code(400);
    echo 'unable to read request body';
    exit;
}

$headers = collect_request_headers();

if (!Verifier::verifyContentDigest($headers, $body)) {
    http_response_code(400);
    echo 'bad content digest';
    exit;
}

$signatureInput = header_value($headers, 'Signature-Input');
$signatureRaw   = header_value($headers, 'Signature');
if ($signatureInput === null || $signatureRaw === null) {
    http_response_code(400);
    echo 'missing signature headers';
    exit;
}

$params = Verifier::parseSignatureInput($signatureInput);
if (!Verifier::signatureIsFresh($params)) {
    http_response_code(400);
    echo 'stale signature';
    exit;
}
if ($params->keyId === null) {
    http_response_code(400);
    echo 'missing key id';
    exit;
}

// Fetch the public key referenced by the signature. publicKey() does not
// require an API key, but creating a Client still wires the same auth /
// logging chain you'd use for other calls.
$client = new Client(apiKey: getenv('MAILCHANNELS_API_KEY') ?: '');
$publicKey = base64_decode((string) $client->webhooks->publicKey($params->keyId)->get('key'), true);
if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
    http_response_code(400);
    echo 'invalid public key';
    exit;
}

// Reconstruct the signature base MailChannels signed, then verify with
// libsodium. The target URI must match the absolute URL the request was
// addressed to — including scheme and any non-default port.
$targetUri  = request_target_uri();
$base       = Verifier::buildSignatureBase('POST', $targetUri, $headers, $body, $params);
$signature  = extract_signature_bytes($signatureRaw, $params->signatureName);

if ($signature === null || !sodium_crypto_sign_verify_detached($signature, $base, $publicKey)) {
    http_response_code(400);
    echo 'bad signature';
    exit;
}

// Verified. Hand the JSON-decoded event off to your handler.
$event = json_decode($body, true);
// process_event($event);
echo 'ok';

// ---------------------------------------------------------------------------

/**
 * Return all incoming request headers as a name => value map. Works under
 * both Apache mod_php (via getallheaders()) and PHP-FPM / nginx / built-in
 * server setups (via $_SERVER scraping). Symfony and Laravel users will
 * normally fetch headers from the framework's request object instead.
 *
 * @return array<string, string>
 */
function collect_request_headers(): array
{
    if (function_exists('getallheaders')) {
        /** @var array<string, string> $h */
        $h = getallheaders();
        return $h;
    }
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (!is_string($key) || !is_string($value)) {
            continue;
        }
        if (str_starts_with($key, 'HTTP_')) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = $value;
        } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
            $name = $key === 'CONTENT_TYPE' ? 'Content-Type' : 'Content-Length';
            $headers[$name] = $value;
        }
    }
    return $headers;
}

/** @param array<string, string> $headers */
function header_value(array $headers, string $name): ?string
{
    $lower = strtolower($name);
    foreach ($headers as $k => $v) {
        if (strtolower($k) === $lower) {
            return $v;
        }
    }
    return null;
}

function request_target_uri(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $uri    = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    return $scheme . '://' . $host . $uri;
}

/**
 * Extract the raw signature bytes for a named signature from the Signature
 * header. RFC 9421 §4.2 encodes each signature as `name=:base64-bytes:`.
 */
function extract_signature_bytes(string $signatureHeader, string $name): ?string
{
    if (preg_match('/' . preg_quote($name, '/') . '=:(?P<sig>[^:]+):/', $signatureHeader, $m) !== 1) {
        return null;
    }
    $decoded = base64_decode($m['sig'], true);
    return $decoded === false ? null : $decoded;
}
