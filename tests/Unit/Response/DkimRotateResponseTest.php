<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\DkimKeyInfo;
use MailChannels\Response\DkimRotateResponse;
use PHPUnit\Framework\TestCase;

final class DkimRotateResponseTest extends TestCase
{
    public function testFromResponseExtractsBothKeys(): void
    {
        $response = new Response(
            data: [
                'new_key' => [
                    'domain' => 'example.com',
                    'selector' => 'mc2',
                    'status' => 'active',
                    'algorithm' => 'rsa',
                    'key_length' => 2048,
                    'dkim_dns_records' => [
                        ['name' => 'mc2._domainkey.example.com', 'type' => 'TXT', 'value' => 'v=DKIM1; ...'],
                    ],
                ],
                'rotated_key' => [
                    'domain' => 'example.com',
                    'selector' => 'mc1',
                    'status' => 'rotated',
                    'gracePeriodExpiresAt' => '2026-06-15T10:00:00Z',
                ],
            ],
            statusCode: 200,
        );

        $typed = DkimRotateResponse::fromResponse($response);

        $this->assertInstanceOf(DkimKeyInfo::class, $typed->newKey);
        $this->assertSame('mc2', $typed->newKey->selector);
        $this->assertSame('active', $typed->newKey->status);
        $this->assertNotNull($typed->newKey->dkimDnsRecords);
        $this->assertCount(1, $typed->newKey->dkimDnsRecords);

        $this->assertInstanceOf(DkimKeyInfo::class, $typed->rotatedKey);
        $this->assertSame('mc1', $typed->rotatedKey->selector);
        $this->assertSame('rotated', $typed->rotatedKey->status);
        $this->assertSame('2026-06-15T10:00:00Z', $typed->rotatedKey->gracePeriodExpiresAt);
    }

    public function testFromResponseLeavesKeysNullWhenBodyEmpty(): void
    {
        $typed = DkimRotateResponse::fromResponse(new Response(data: [], statusCode: 200));

        $this->assertNull($typed->newKey);
        $this->assertNull($typed->rotatedKey);
    }

    public function testFromResponseRejectsNewKeyAsList(): void
    {
        $response = new Response(
            data: ['new_key' => ['mc2', 'mc3']],  // list instead of assoc
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'new_key' must be an associative array");
        DkimRotateResponse::fromResponse($response);
    }

    public function testPreservesHttpMetadata(): void
    {
        $response = new Response(
            data: ['new_key' => ['selector' => 'mc2']],
            statusCode: 200,
            headers: ['X-Request-ID' => 'rid-dkim-rotate'],
            rawBody: '{"new_key":{"selector":"mc2"}}',
        );

        $typed = DkimRotateResponse::fromResponse($response);

        $this->assertSame(200, $typed->statusCode);
        $this->assertSame('rid-dkim-rotate', $typed->requestId());
    }
}
