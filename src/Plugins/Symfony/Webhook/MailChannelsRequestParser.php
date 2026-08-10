<?php

namespace MailChannels\Plugins\Symfony\Webhook;

use MailChannels\Client;
use MailChannels\Exception\MailChannelsException;
use MailChannels\Plugins\Symfony\RemoteEvent\MailChannelsPayloadConverter;
use MailChannels\Webhook\Verifier;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\PayloadConverterInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

class MailChannelsRequestParser extends AbstractRequestParser
{
    // Map of key_name : [public_key, creation_timestamp]
    /** @var array<string, array{0: string, 1: int}> $storedKeys */
    private array $storedKeys = [];

    private int $maxKeyAgeSeconds = 300; // 5 minutes

    private Client $client;
    private PayloadConverterInterface $converter;

    // ASN.1 SubjectPublicKeyInfo prefix for an Ed25519 key, base64-encoded. Prepended to key in PEM files.
    private const PEM_PREFIX = "MCowBQYDK2VwAyEA";

    public function __construct(Client $client = null) {
        $this->client = $client ?? new Client();
        $this->converter = new MailChannelsPayloadConverter();
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new MethodRequestMatcher('POST'),
            new IsJsonRequestMatcher(),
        ]);
    }

    /** @return RemoteEvent|array<RemoteEvent>|null */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent|array|null
    {
        // Access headers via lowercase keys
        $headers = $this->flattenHeaders($request->headers->all());
        if (\count($headers) < 1) {
            throw new RejectWebhookException(400, message:'No request headers.');
        }

        if (!Verifier::verifyContentDigest($headers, $request->getContent())) {
            throw new RejectWebhookException(400, 'Content digest mismatch.');
        }

        $params = Verifier::parseSignatureInput($headers['signature-input'] ?? '');
        if (!Verifier::signatureIsFresh($params)) {
            throw new RejectWebhookException(400, 'Signature is stale.');
        }
        $keyId = $params->keyId ?? '';
        if (!$keyId) {
            throw new RejectWebhookException(400, 'Signature-Input header missing keyid parameter.');
        }

        $publicKey = $this->getStoredKey($keyId);
        if (!$publicKey) {
            $publicKey = $this->fetchNewKey($keyId);
            $this->storeKey($keyId, $publicKey);
        }

        if (!is_string($request->getContent()) || !$request->getContent()) {
            throw new RejectWebhookException(400, 'Request body is not a string.');
        }
        $signatureBase = Verifier::buildSignatureBase('POST', $request->getUri(), $headers, $request->getContent(), $params);

        if (!preg_match('/:[^:]+:$/', $headers['signature'] ?? '', $m)) {
            throw new RejectWebhookException(400, 'Unable to parse Signature header');
        }
        $signatureBytes  = base64_decode(trim($m[0], ':'), true);
        if ($signatureBytes === false || $signatureBytes === '') {
            throw new RejectWebhookException(400, 'Unable to decode signature.');
        }

        try {
            if (!sodium_crypto_sign_verify_detached($signatureBytes, $signatureBase, $publicKey)) {
                throw new RejectWebhookException(400, 'Webhook signature did not match public key.');
            }
        } catch (\SodiumException $e) {
            throw new RejectWebhookException(500, message:'Error verifying signature', previous: $e);
        }

        /** @var array<string, array<string, mixed>> $payload */
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return null;
        }

        $events = [];
        foreach ($payload as $event) {
            if (!\is_array($event)) {
                throw new RejectWebhookException(400, 'Invalid event in payload: not an array');
            }
            $events[] = $this->converter->convert($event);
        }

        return $events;
    }

    private function storeKey(string $keyId, string $publicKey): void {
        /** @var array{0: string, 1: int} $key */
        $key = [$publicKey, time()];
        $this->storedKeys[$keyId] = $key;
    }

    private function getStoredKey(string $keyId): ?string {
        $key = $this->storedKeys[$keyId] ?? null;
        if (!$key || sizeof($key) != 2) {
            return null;
        }

        $publicKey = $key[0];
        $timeCreated = $key[1];
        if ($timeCreated + $this->maxKeyAgeSeconds < time()) {
            return null;
        }

        return $publicKey;
    }

    /** @return non-empty-string */
    private function fetchNewKey(string $keyId): string {
        try {
            $publicKeyObj = $this->client->webhooks->publicKey($keyId);
            $id = $publicKeyObj->id;
            $unparsedKey = $publicKeyObj->key;
            if (!$id || !$unparsedKey) {
                throw new RejectWebhookException(500, 'Public key response missing required field.');
            }

            $publicKey = $this->parseKey($unparsedKey);
            if (!$publicKey) {
                throw new RejectWebhookException(500, "Unable to parse public key");
            }

            return $publicKey;
        } catch (MailChannelsException) {
            throw new RejectWebhookException(500, 'Unable to fetch public key for key ID: ' . $keyId);
        }
    }

    private function parseKey(string $publicKey): ?string {
        $publicKey = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n", "\r", " "], '', $publicKey);

        if (str_starts_with($publicKey, self::PEM_PREFIX)) {
            $publicKey = substr($publicKey, strlen(self::PEM_PREFIX));
        } else {
            return null;
        }

        $decodedPublicKey = base64_decode($publicKey, true);
        if ($decodedPublicKey === false || $decodedPublicKey === '') {
            return null;
        }

        if (strlen($decodedPublicKey) !== 32) {
            return null;
        }

        return $decodedPublicKey;
    }

    /** @param array<string, list<string|null>> $headers
     * @return array<string, string>
     * */
    private function flattenHeaders(array $headers): array {
        $flattened = [];
        foreach ($headers as $key => $values) {
            if (is_array($values) && count($values) > 0 && is_string($values[0])) {
                $flattened[strtolower($key)] = $values[0];
            }
        }
        return $flattened;
    }
}
