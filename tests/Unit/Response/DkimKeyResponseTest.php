<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\DkimKeyInfo;
use MailChannels\Response\DkimKeyResponse;
use PHPUnit\Framework\TestCase;

final class DkimKeyResponseTest extends TestCase
{
    public function testFromResponseBuildsKeyFromBody(): void
    {
        // Per the OpenAPI spec, POST /domains/{domain}/dkim-keys returns a
        // DKIMKeyInfo *directly* as the response body — there is no wrapper
        // key in the JSON.
        $body = [
            'domain' => 'example.com',
            'selector' => 'mc1',
            'public_key' => 'AAAAAA',
            'status' => 'active',
            'algorithm' => 'rsa',
            'key_length' => 2048,
            'created_at' => '2026-06-01T10:00:00Z',
            'dkim_dns_records' => [
                ['name' => 'mc1._domainkey.example.com', 'type' => 'TXT', 'value' => 'v=DKIM1; ...'],
            ],
        ];

        $typed = DkimKeyResponse::fromResponse(new Response(data: $body, statusCode: 201));

        $this->assertInstanceOf(DkimKeyInfo::class, $typed->key);
        $this->assertSame('example.com', $typed->key->domain);
        $this->assertSame('mc1', $typed->key->selector);
        $this->assertSame('AAAAAA', $typed->key->publicKey);
        $this->assertSame('active', $typed->key->status);
        $this->assertSame('rsa', $typed->key->algorithm);
        $this->assertSame(2048, $typed->key->keyLength);
        $this->assertNotNull($typed->key->dkimDnsRecords);
        $this->assertCount(1, $typed->key->dkimDnsRecords);
    }

    public function testFromResponseLeavesKeyNullForEmptyBody(): void
    {
        $typed = DkimKeyResponse::fromResponse(new Response(data: [], statusCode: 201));

        $this->assertNull($typed->key);
    }

    public function testFromResponseRejectsWrongTypeInsideKey(): void
    {
        $body = [
            'domain' => 'example.com',
            'selector' => 'mc1',
            'key_length' => '2048',  // string, not int
        ];

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'key_length' must be an int");
        DkimKeyResponse::fromResponse(new Response(data: $body, statusCode: 201));
    }

    public function testFromResponseHonorsBothWireFormsForApiQuirkFields(): void
    {
        // The body fields gracePeriodExpiresAt / retiresAt are camelCase in
        // the v1 API; the SDK accepts both wire forms so a future fix is
        // transparent.
        $body = [
            'selector' => 'mc1',
            'grace_period_expires_at' => '2026-06-15T10:00:00Z',  // hypothetical v2
        ];

        $typed = DkimKeyResponse::fromResponse(new Response(data: $body, statusCode: 201));

        $this->assertNotNull($typed->key);
        $this->assertSame('2026-06-15T10:00:00Z', $typed->key->gracePeriodExpiresAt);
    }

    public function testPreservesHttpMetadata(): void
    {
        $response = new Response(
            data: ['selector' => 'mc1'],
            statusCode: 201,
            headers: ['X-Request-ID' => 'rid-dkim-create'],
            rawBody: '{"selector":"mc1"}',
        );

        $typed = DkimKeyResponse::fromResponse($response);

        $this->assertSame(201, $typed->statusCode);
        $this->assertSame('rid-dkim-create', $typed->requestId());
        $this->assertSame('{"selector":"mc1"}', $typed->rawBody);
    }
}
