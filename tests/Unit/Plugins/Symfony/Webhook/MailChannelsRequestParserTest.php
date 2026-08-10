<?php

namespace MailChannels\Tests\Unit\Plugins\Symfony\Webhook;

use MailChannels\Plugins\Symfony\Webhook\MailChannelsRequestParser;
use MailChannels\Tests\Support\Fixtures;
use MailChannels\Tests\Support\MockHttpClient;
use MailChannels\Webhook\Verifier;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerEngagementEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

class MailChannelsRequestParserTest extends TestCase
{
    private const TARGET_URI = 'https://hooks.example.com/mailchannels';
    private const KEY_ID = 'test-key-1';

    // ASN.1 SubjectPublicKeyInfo prefix for an Ed25519 key, base64-encoded. Prepended to key in PEM files.
    private const KEY_DER_PREFIX = 'MCowBQYDK2VwAyEA';

    private AbstractRequestParser $parser;
    private MockHttpClient $http;

    private string $processed = '
    {
        "email": "sender@example.com",
        "customer_handle": "abc123",
        "timestamp": 1625097600,
        "event": "processed",
        "request_id": "wBLWrCnK0Z965pf-cgxhNg8bo5s=",
        "campaign_id": "campaign_123",
        "recipients": ["recipient@example.com", "recipient2@example.com"]
    }';

    private string $dropped = '
    {
        "email": "sender@example.com",
        "customer_handle": "abc123",
        "timestamp": 1625097600,
        "event": "dropped",
        "request_id": "wBLWrCnK0Z965pf-cgxhNg8bo5s=",
        "status": "451",
        "reason": "Spam detected",
        "recipients": ["recipient@example.com"]
    }';

    private string $clicked = '
    {
        "email": "sender@example.com",
        "customer_handle": "abc123",
        "timestamp": 1625097600,
        "event": "click",
        "request_id": "wBLWrCnK0Z965pf-cgxhNg8bo5s=",
        "url": "example.com",
        "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3",
        "smtp_id": "<1625097500@example.com>",
        "ip": "127.0.0.1",
        "recipients": ["recipient@example.com"]
    }';

    private string $batch;

    protected function setUp(): void
    {
        parent::setUp();
        if (!\function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('libsodium is not available.');
        }
        $this->http = new MockHttpClient();
        $this->parser = new MailChannelsRequestParser(Fixtures::client($this->http));
        $this->batch = '['.$this->processed.','.$this->dropped.','.$this->clicked.']';
    }

    public function testSuccessfulProcessedEventBatch(): void
    {
        [$request, $keyId, $publicKey] = $this->buildSignedRequest($this->batch);
        $this->queuePublicKeyResponse($keyId, $publicKey);

        $events = $this->parser->parse($request, '');

        $this->assertIsArray($events);
        $this->assertCount(3, $events);

        $this->assertInstanceOf(MailerDeliveryEvent::class, $events[0]);
        $this->assertEquals(MailerDeliveryEvent::RECEIVED, $events[0]->getName());
        $this->assertEquals('recipient@example.com', $events[0]->getRecipientEmail());

        $this->assertInstanceOf(MailerDeliveryEvent::class, $events[1]);
        $this->assertEquals(MailerDeliveryEvent::DROPPED, $events[1]->getName());
        $this->assertEquals('Spam detected', $events[1]->getReason());

        $this->assertInstanceOf(MailerEngagementEvent::class, $events[2]);
        $this->assertEquals(MailerEngagementEvent::CLICK, $events[2]->getName());
    }

    public function testRejectsRequestWithNoHeaders(): void
    {
        $request = Request::create(self::TARGET_URI, 'POST', [], [], [], [], '[]');
        $request->headers->replace([]);

        $exception = $this->assertRejects($request);

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('No request headers.', $exception->getMessage());
    }

    public function testRejectsOnContentDigestMismatch(): void
    {
        [$request] = $this->buildSignedRequest(
            $this->batch,
            digest: 'sha-256=:'.base64_encode(hash('sha256', 'not-the-body', true)).':',
        );

        $exception = $this->assertRejects($request);

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('Content digest mismatch.', $exception->getMessage());
    }

    public function testRejectsStaleSignature(): void
    {
        [$request] = $this->buildSignedRequest($this->batch, createdOffset: -1000);

        $exception = $this->assertRejects($request);

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('Signature is stale.', $exception->getMessage());
    }

    public function testRejectsSignatureInputMissingKeyId(): void
    {
        [$request] = $this->buildSignedRequest($this->batch, omitKeyId: true);

        $exception = $this->assertRejects($request);

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('Signature-Input header missing keyid parameter.', $exception->getMessage());
    }

    public function testRejectsWhenSignatureDoesNotMatchPublicKey(): void
    {
        [$request, $keyId, $publicKey] = $this->buildSignedRequest($this->batch, tamperSignature: true);
        $this->queuePublicKeyResponse($keyId, $publicKey);

        $exception = $this->assertRejects($request);

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('Webhook signature did not match public key.', $exception->getMessage());
    }

    public function testRejectsPayloadContainingNonArrayEvent(): void
    {
        $body = '["not-an-event-object"]';
        [$request, $keyId, $publicKey] = $this->buildSignedRequest($body);
        $this->queuePublicKeyResponse($keyId, $publicKey);

        $exception = $this->assertRejects($request);

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('Invalid event in payload: not an array', $exception->getMessage());
    }

    public function testReturnsNullWhenPayloadIsNotAnArray(): void
    {
        $body = '"just a string"';
        [$request, $keyId, $publicKey] = $this->buildSignedRequest($body);
        $this->queuePublicKeyResponse($keyId, $publicKey);

        $this->assertNull($this->parser->parse($request, ''));
    }

    public function testFetchesPublicKeyFromServerWithoutApiKeyAndCachesIt(): void
    {
        $keypair = sodium_crypto_sign_keypair();
        [$request, $keyId, $publicKey] = $this->buildSignedRequest($this->batch, keypair: $keypair);
        $this->queuePublicKeyResponse($keyId, $publicKey);

        $this->parser->parse($request, '');
        [$secondRequest] = $this->buildSignedRequest($this->batch, keypair: $keypair);
        $this->parser->parse($secondRequest, '');

        $this->assertCount(1, $this->http->sentRequests);
        $lastRequest = $this->http->lastRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertStringContainsString('/webhook/public-key', (string) $lastRequest->getUri());
        $this->assertStringContainsString('id='.$keyId, $lastRequest->getUri()->getQuery());
        $this->assertFalse($lastRequest->hasHeader('X-Api-Key'));
    }

    public function testFetchesPublicKeyFromServerWithoutApiKeyExpiredCacheEntry(): void
    {
        $keypair = sodium_crypto_sign_keypair();
        [$request, $keyId, $publicKey] = $this->buildSignedRequest($this->batch, keypair: $keypair);
        $this->queuePublicKeyResponse($keyId, $publicKey);
        $this->queuePublicKeyResponse($keyId, $publicKey);

        $this->parser->parse($request, '');

        $property = (new ReflectionClass($this->parser))->getProperty('storedKeys');
        $property->setAccessible(true);
        $property->setValue($this->parser, [$keyId => [$publicKey, time() - 1000]]);

        [$secondRequest] = $this->buildSignedRequest($this->batch, keypair: $keypair);
        $this->parser->parse($secondRequest, '');

        $this->assertCount(2, $this->http->sentRequests);
        $lastRequest = $this->http->lastRequest();
        $this->assertSame('GET', $lastRequest->getMethod());
        $this->assertStringContainsString('/webhook/public-key', (string) $lastRequest->getUri());
        $this->assertStringContainsString('id='.$keyId, $lastRequest->getUri()->getQuery());
        $this->assertFalse($lastRequest->hasHeader('X-Api-Key'));
    }

    public function testRejectsWhenPublicKeyResponseIsMissingKeyMaterial(): void
    {
        [$request, $keyId] = $this->buildSignedRequest($this->batch);
        $this->http->queue(Fixtures::jsonResponse(200, ['id' => $keyId]));

        $exception = $this->assertRejects($request);

        $this->assertSame(500, $exception->getStatusCode());
        $this->assertSame('Public key response missing required field.', $exception->getMessage());
    }

    public function testRejectsWhenPublicKeyCannotBeParsed(): void
    {
        [$request, $keyId] = $this->buildSignedRequest($this->batch);
        $this->http->queue(Fixtures::jsonResponse(200, ['id' => $keyId, 'key' => 'not-a-valid-ed25519-key']));

        $exception = $this->assertRejects($request);

        $this->assertSame(500, $exception->getStatusCode());
        $this->assertSame('Unable to parse public key', $exception->getMessage());
    }

    public function testRejectsWhenPublicKeyFetchFails(): void
    {
        [$request, $keyId] = $this->buildSignedRequest($this->batch);
        $this->http->queue(Fixtures::jsonResponse(404, ['message' => 'Unknown key ID']));

        $exception = $this->assertRejects($request);

        $this->assertSame(500, $exception->getStatusCode());
        $this->assertSame('Unable to fetch public key for key ID: '.$keyId, $exception->getMessage());
    }

    private function assertRejects(Request $request): RejectWebhookException
    {
        try {
            $this->parser->parse($request, '');
            $this->fail('Expected a RejectWebhookException to be thrown.');
        } catch (RejectWebhookException $e) {
            return $e;
        }
    }

    /**
     * Builds a POST request carrying a valid Content-Digest header and an
     * RFC 9421 Signature-Input/Signature pair over $body.
     * Set createdOffset to change when webhook was signed
     * Set omitKeyId to remove keyid from signature
     * Set tamperSignature to apply an invalid signature value
     * Set $keypair (from sodium_crypto_sign_keypair()) to sign multiple requests with
     *  the same key
     *
     * @param non-empty-string|null $keypair
     * @return array{0: Request, 1: string, 2: string} [request, keyId, ed25519 public key bytes]
     * @throws \SodiumException
     */
    private function buildSignedRequest(
        string $body,
        ?string $digest = null,
        int $createdOffset = 0,
        bool $omitKeyId = false,
        bool $tamperSignature = false,
        ?string $keypair = null,
    ): array {
        $digest ??= 'sha-256=:'.base64_encode(hash('sha256', $body, true)).':';
        $created = time() + $createdOffset;

        $signatureInputValue = $omitKeyId
            ? \sprintf('sig1=("@method" "@target-uri" "content-digest");created=%d;alg="ed25519"', $created)
            : \sprintf('sig1=("@method" "@target-uri" "content-digest");created=%d;keyid="%s";alg="ed25519"', $created, self::KEY_ID);

        $keypair ??= sodium_crypto_sign_keypair();
        $secretKey = sodium_crypto_sign_secretkey($keypair);
        $publicKey = sodium_crypto_sign_publickey($keypair);

        $params = Verifier::parseSignatureInput($signatureInputValue);
        $signatureBase = Verifier::buildSignatureBase('POST', self::TARGET_URI, ['content-digest' => $digest], $body, $params);
        $signatureBytes = sodium_crypto_sign_detached($signatureBase, $secretKey);
        if ($tamperSignature) {
            $signatureBytes[0] = \chr(\ord($signatureBytes[0]) ^ 0x0F);
        }

        $request = Request::create(self::TARGET_URI, 'POST', [], [], [], [], $body);
        $request->headers->set('Content-Digest', $digest);
        $request->headers->set('Signature-Input', $signatureInputValue);
        $request->headers->set('Signature', 'sig1=:'.base64_encode($signatureBytes).':');

        return [$request, self::KEY_ID, $publicKey];
    }

    private function queuePublicKeyResponse(string $keyId, string $rawPublicKey): void
    {
        $this->http->queue(Fixtures::jsonResponse(200, [
            'id' => $keyId,
            'key' => $this->encodePublicKeyAsPem($rawPublicKey),
        ]));
    }

    /** Wraps raw Ed25519 key bytes in the SPKI DER + PEM envelope the real API returns. */
    private function encodePublicKeyAsPem(string $rawPublicKey): string
    {
        $der = base64_decode(self::KEY_DER_PREFIX, true).$rawPublicKey;
        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64)."-----END PUBLIC KEY-----\n";
    }
}
