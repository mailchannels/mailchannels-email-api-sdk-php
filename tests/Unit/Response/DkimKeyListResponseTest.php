<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\DkimDnsRecord;
use MailChannels\Response\DkimKeyInfo;
use MailChannels\Response\DkimKeyListResponse;
use PHPUnit\Framework\TestCase;

final class DkimKeyListResponseTest extends TestCase
{
    public function testFromResponseExtractsTypedKeysAndNestedDnsRecords(): void
    {
        $response = new Response(
            data: [
                'keys' => [
                    [
                        'domain' => 'example.com',
                        'selector' => 'mc1',
                        'public_key' => 'AAAAAA',
                        'status' => 'active',
                        'algorithm' => 'rsa',
                        'key_length' => 2048,
                        'created_at' => '2026-05-01T10:00:00Z',
                        'status_modified_at' => '2026-05-15T10:00:00Z',
                        'gracePeriodExpiresAt' => '2026-06-15T10:00:00Z',
                        'retiresAt' => '2026-07-01T10:00:00Z',
                        'dkim_dns_records' => [
                            ['name' => 'mc1._domainkey.example.com', 'type' => 'TXT', 'value' => 'v=DKIM1; ...'],
                        ],
                    ],
                ],
            ],
            statusCode: 200,
        );

        $typed = new DkimKeyListResponse($response);

        $this->assertNotNull($typed->keys);
        $this->assertCount(1, $typed->keys);
        $key = $typed->keys[0];
        $this->assertInstanceOf(DkimKeyInfo::class, $key);
        $this->assertSame('example.com', $key->domain);
        $this->assertSame('mc1', $key->selector);
        $this->assertSame('AAAAAA', $key->publicKey);
        $this->assertSame('active', $key->status);
        $this->assertSame('rsa', $key->algorithm);
        $this->assertSame(2048, $key->keyLength);
        $this->assertSame('2026-05-01T10:00:00Z', $key->createdAt);
        $this->assertSame('2026-05-15T10:00:00Z', $key->statusModifiedAt);
        $this->assertSame('2026-06-15T10:00:00Z', $key->gracePeriodExpiresAt);
        $this->assertSame('2026-07-01T10:00:00Z', $key->retiresAt);

        $this->assertNotNull($key->dkimDnsRecords);
        $this->assertCount(1, $key->dkimDnsRecords);
        $record = $key->dkimDnsRecords[0];
        $this->assertInstanceOf(DkimDnsRecord::class, $record);
        $this->assertSame('mc1._domainkey.example.com', $record->name);
        $this->assertSame('TXT', $record->type);
        $this->assertSame('v=DKIM1; ...', $record->value);
    }

    public function testFromResponseLeavesKeysNullWhenBodyEmpty(): void
    {
        $typed = new DkimKeyListResponse(new Response(data: [], statusCode: 200));

        $this->assertNull($typed->keys);
    }

    public function testFromResponseAcceptsEmptyKeysList(): void
    {
        $typed = new DkimKeyListResponse(
            new Response(data: ['keys' => []], statusCode: 200),
        );

        $this->assertSame([], $typed->keys);
    }

    public function testFromResponseRejectsKeysNotAList(): void
    {
        $response = new Response(
            data: ['keys' => ['domain' => 'example.com']],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'keys' must be a list");
        new DkimKeyListResponse($response);
    }

    public function testFromResponseRejectsKeyEntryWithWrongTypeKeyLength(): void
    {
        $response = new Response(
            data: [
                'keys' => [
                    ['domain' => 'example.com', 'key_length' => '2048'],  // string, not int
                ],
            ],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'key_length' must be an int");
        new DkimKeyListResponse($response);
    }

    public function testPreservesHttpMetadata(): void
    {
        $response = new Response(
            data: ['keys' => []],
            statusCode: 200,
            headers: ['X-Request-ID' => 'rid-dkim-list'],
            rawBody: '{"keys":[]}',
        );

        $typed = new DkimKeyListResponse($response);

        $this->assertSame(200, $typed->statusCode);
        $this->assertSame('rid-dkim-list', $typed->requestId());
    }

    public function testDkimDnsRecordJsonSerializeOmitsNullFields(): void
    {
        $record = new DkimDnsRecord(name: 'mc._domainkey.example.com', type: 'TXT');

        $this->assertSame(
            ['name' => 'mc._domainkey.example.com', 'type' => 'TXT'],
            $record->jsonSerialize(),
        );
    }

    public function testFromResponseReadsSnakeCaseFormOfApiQuirkFields(): void
    {
        // Forward-compat: if a future API version normalizes the camelCase
        // outliers (gracePeriodExpiresAt / retiresAt) to snake_case, the SDK
        // should already be reading them transparently.
        $response = new Response(
            data: [
                'keys' => [
                    [
                        'selector' => 'mc1',
                        'grace_period_expires_at' => '2026-06-15T10:00:00Z',
                        'retires_at' => '2026-07-01T10:00:00Z',
                    ],
                ],
            ],
            statusCode: 200,
        );

        $typed = new DkimKeyListResponse($response);

        $this->assertNotNull($typed->keys);
        $this->assertSame('2026-06-15T10:00:00Z', $typed->keys[0]->gracePeriodExpiresAt);
        $this->assertSame('2026-07-01T10:00:00Z', $typed->keys[0]->retiresAt);
    }

    public function testSnakeCaseWinsWhenBothFormsArePresent(): void
    {
        // During a hypothetical transition where the API emits both forms,
        // the SDK prefers the normalized snake_case value.
        $response = new Response(
            data: [
                'keys' => [
                    [
                        'selector' => 'mc1',
                        'grace_period_expires_at' => '2026-06-15T10:00:00Z',
                        'gracePeriodExpiresAt' => '2099-12-31T23:59:59Z',
                    ],
                ],
            ],
            statusCode: 200,
        );

        $typed = new DkimKeyListResponse($response);

        $this->assertNotNull($typed->keys);
        $this->assertSame('2026-06-15T10:00:00Z', $typed->keys[0]->gracePeriodExpiresAt);
    }

    public function testDkimKeyInfoJsonSerializeEmitsSnakeCaseForApiQuirkFields(): void
    {
        // Round-trip output uses the normalized snake_case form, hiding the
        // API quirk from anyone who logs or re-serializes the value object.
        $info = new DkimKeyInfo(
            selector: 'mc1',
            gracePeriodExpiresAt: '2026-06-15T10:00:00Z',
            retiresAt: '2026-07-01T10:00:00Z',
        );

        $this->assertSame(
            [
                'selector' => 'mc1',
                'grace_period_expires_at' => '2026-06-15T10:00:00Z',
                'retires_at' => '2026-07-01T10:00:00Z',
            ],
            $info->jsonSerialize(),
        );
    }
}
