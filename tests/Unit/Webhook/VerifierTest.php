<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Webhook;

use MailChannels\Webhook\Verifier;
use PHPUnit\Framework\TestCase;

final class VerifierTest extends TestCase
{
    public function testParseSignatureInputExtractsComponents(): void
    {
        $params = Verifier::parseSignatureInput(
            'sig1=("@method" "content-digest" "@target-uri");created=1747000000;keyid="key-42";alg="ed25519"'
        );
        $this->assertSame('sig1', $params->signatureName);
        $this->assertSame(['@method', 'content-digest', '@target-uri'], $params->coveredComponents);
        $this->assertSame(1747000000, $params->created);
        $this->assertSame('key-42', $params->keyId);
        $this->assertSame('ed25519', $params->algorithm);
    }

    public function testSignatureKeyIdReadsFromHeaders(): void
    {
        $headers = [
            'Signature-Input' => 'sig1=("@method");created=1747000000;keyid="my-key"',
        ];
        $this->assertSame('my-key', Verifier::signatureKeyId($headers));
    }

    public function testSignatureKeyIdReadsCaseInsensitively(): void
    {
        $headers = [
            'signature-input' => 'sig1=("@method");keyid="lower-key"',
        ];
        $this->assertSame('lower-key', Verifier::signatureKeyId($headers));
    }

    public function testFreshnessWithinTolerance(): void
    {
        $params = Verifier::parseSignatureInput('s=("@method");created=1000000');
        $this->assertTrue(Verifier::signatureIsFresh($params, toleranceSeconds: 300, now: 1000200));
        $this->assertFalse(Verifier::signatureIsFresh($params, toleranceSeconds: 300, now: 1001000));
    }

    public function testFreshnessFailsWithoutCreated(): void
    {
        $params = Verifier::parseSignatureInput('s=("@method")');
        $this->assertFalse(Verifier::signatureIsFresh($params));
    }

    public function testVerifyContentDigestPasses(): void
    {
        $body = '{"event":"delivered"}';
        $digest = 'sha-256=:' . base64_encode(hash('sha256', $body, true)) . ':';
        $this->assertTrue(Verifier::verifyContentDigest(['Content-Digest' => $digest], $body));
    }

    public function testVerifyContentDigestFailsOnMismatch(): void
    {
        $digest = 'sha-256=:' . base64_encode(hash('sha256', 'other', true)) . ':';
        $this->assertFalse(Verifier::verifyContentDigest(['Content-Digest' => $digest], 'wrong'));
    }

    public function testVerifyContentDigestFailsWithoutHeader(): void
    {
        $this->assertFalse(Verifier::verifyContentDigest([], 'anything'));
    }

    public function testBuildSignatureBaseProducesCanonicalForm(): void
    {
        $signatureInput = 'sig1=("@method" "@target-uri" "@authority" "@path" "content-digest");'
            . 'created=1747000000;keyid="mc-key-1";alg="ed25519"';
        $params = Verifier::parseSignatureInput($signatureInput);

        $body = '{"event":"delivered"}';
        $headers = [
            'Content-Digest' => 'sha-256=:' . base64_encode(hash('sha256', $body, true)) . ':',
            'Content-Type' => 'application/json',
        ];

        $base = Verifier::buildSignatureBase(
            method: 'POST',
            targetUri: 'https://hooks.example.com/mc',
            headers: $headers,
            body: $body,
            parameters: $params,
        );

        $expected = implode("\n", [
            '"@method": POST',
            '"@target-uri": https://hooks.example.com/mc',
            '"@authority": hooks.example.com',
            '"@path": /mc',
            '"content-digest": ' . $headers['Content-Digest'],
            '"@signature-params": ("@method" "@target-uri" "@authority" "@path" "content-digest");'
                . 'created=1747000000;keyid="mc-key-1";alg="ed25519"',
        ]);
        $this->assertSame($expected, $base);
    }

    public function testBuildSignatureBaseHandlesNonDefaultPortInAuthority(): void
    {
        $params = Verifier::parseSignatureInput('s=("@authority");created=1');
        $base = Verifier::buildSignatureBase('POST', 'https://example.com:8443/x', [], '', $params);
        $this->assertStringContainsString('"@authority": example.com:8443', $base);
    }

    public function testBuildSignatureBaseOmitsDefaultPortInAuthority(): void
    {
        $params = Verifier::parseSignatureInput('s=("@authority");created=1');
        $base = Verifier::buildSignatureBase('POST', 'https://example.com:443/x', [], '', $params);
        $this->assertStringContainsString('"@authority": example.com', $base);
        $this->assertStringNotContainsString(':443', $base);
    }

    public function testBuildSignatureBaseSupportsRequestTargetAndQuery(): void
    {
        $params = Verifier::parseSignatureInput('s=("@request-target" "@query");created=1');
        $base = Verifier::buildSignatureBase('POST', 'https://x.com/a/b?c=1&d=2', [], '', $params);
        $this->assertStringContainsString('"@request-target": /a/b?c=1&d=2', $base);
        $this->assertStringContainsString('"@query": ?c=1&d=2', $base);
    }

    public function testBuildSignatureBaseEmptyQueryIsBareQuestionMark(): void
    {
        $params = Verifier::parseSignatureInput('s=("@query");created=1');
        $base = Verifier::buildSignatureBase('POST', 'https://x.com/a', [], '', $params);
        $this->assertStringContainsString('"@query": ?', $base);
    }

    public function testBuildSignatureBaseTrimsHeaderValues(): void
    {
        $params = Verifier::parseSignatureInput('s=("x-custom");created=1');
        $base = Verifier::buildSignatureBase('POST', 'https://x.com/a', ['X-Custom' => '  hello  '], '', $params);
        $this->assertStringContainsString('"x-custom": hello', $base);
    }

    public function testBuildSignatureBaseRaisesOnMissingHeader(): void
    {
        $params = Verifier::parseSignatureInput('s=("content-digest");created=1');
        $this->expectException(\InvalidArgumentException::class);
        Verifier::buildSignatureBase('POST', 'https://x.com/a', [], '', $params);
    }

    public function testBuildSignatureBaseRaisesOnUnknownDerivedComponent(): void
    {
        $params = Verifier::parseSignatureInput('s=("@made-up");created=1');
        $this->expectException(\InvalidArgumentException::class);
        Verifier::buildSignatureBase('POST', 'https://x.com/a', [], '', $params);
    }

    public function testBuildSignatureBaseVerifiesEnd2EndWithLibsodium(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('libsodium is not available.');
        }
        $keypair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($keypair);
        $public = sodium_crypto_sign_publickey($keypair);

        $body = '{"event":"delivered","customer_handle":"acme"}';
        $headers = [
            'Content-Digest' => 'sha-256=:' . base64_encode(hash('sha256', $body, true)) . ':',
        ];
        $signatureInput = 'sig1=("@method" "@target-uri" "content-digest");created=' . time() . ';keyid="k1";alg="ed25519"';
        $params = Verifier::parseSignatureInput($signatureInput);

        $base = Verifier::buildSignatureBase('POST', 'https://hooks.example.com/mc', $headers, $body, $params);
        $signature = sodium_crypto_sign_detached($base, $secret);

        $this->assertTrue(sodium_crypto_sign_verify_detached($signature, $base, $public));
        $this->assertFalse(
            sodium_crypto_sign_verify_detached($signature, $base . 'tamper', $public),
            'Tampered base must fail verification.',
        );
    }
}
