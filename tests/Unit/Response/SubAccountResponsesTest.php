<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\SubAccountApiKey;
use MailChannels\Response\SubAccountApiKeyListResponse;
use MailChannels\Response\SubAccountApiKeyResponse;
use MailChannels\Response\SubAccountDetails;
use MailChannels\Response\SubAccountLimitResponse;
use MailChannels\Response\SubAccountListResponse;
use MailChannels\Response\SubAccountResponse;
use MailChannels\Response\SubAccountSmtpPassword;
use MailChannels\Response\SubAccountSmtpPasswordListResponse;
use MailChannels\Response\SubAccountSmtpPasswordResponse;
use PHPUnit\Framework\TestCase;

final class SubAccountResponsesTest extends TestCase
{
    // ----- POST /sub-account (create) -----

    public function testCreateSubAccountResponseExtractsTypedFields(): void
    {
        $body = ['handle' => 'tenant42', 'company_name' => 'ACME Co', 'enabled' => true];

        $typed = SubAccountResponse::fromResponse(new Response(data: $body, statusCode: 201));

        $this->assertInstanceOf(SubAccountDetails::class, $typed->subAccount);
        $this->assertSame('tenant42', $typed->subAccount->handle);
        $this->assertSame('ACME Co', $typed->subAccount->companyName);
        $this->assertTrue($typed->subAccount->enabled);
    }

    public function testCreateSubAccountResponseLeavesAccountNullOnEmptyBody(): void
    {
        $typed = SubAccountResponse::fromResponse(new Response(data: [], statusCode: 201));
        $this->assertNull($typed->subAccount);
    }

    public function testCreateSubAccountResponseRejectsWrongTypeEnabled(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'enabled' must be a bool");
        SubAccountResponse::fromResponse(new Response(
            data: ['handle' => 't1', 'enabled' => 'true'],
            statusCode: 201,
        ));
    }

    // ----- GET /sub-account (list, bare array) -----

    public function testSubAccountListResponseExtractsTypedEntries(): void
    {
        // Bare-array response → Transport wraps as ['data' => [...]].
        $body = [
            'data' => [
                ['handle' => 'tenant1', 'company_name' => 'One Co', 'enabled' => true],
                ['handle' => 'tenant2', 'company_name' => 'Two Co', 'enabled' => false],
            ],
        ];

        $typed = SubAccountListResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertNotNull($typed->subAccounts);
        $this->assertCount(2, $typed->subAccounts);
        $this->assertSame('tenant1', $typed->subAccounts[0]->handle);
        $this->assertTrue($typed->subAccounts[0]->enabled);
        $this->assertFalse($typed->subAccounts[1]->enabled);
    }

    public function testSubAccountListResponseLeavesListNullOnEmptyBody(): void
    {
        $typed = SubAccountListResponse::fromResponse(new Response(data: [], statusCode: 200));
        $this->assertNull($typed->subAccounts);
    }

    // ----- POST /sub-account/{handle}/api-key (create) -----

    public function testApiKeyCreateResponseExtractsTypedFields(): void
    {
        $body = ['id' => 42, 'key' => 'mc-secret-12345'];

        $typed = SubAccountApiKeyResponse::fromResponse(new Response(data: $body, statusCode: 201));

        $this->assertInstanceOf(SubAccountApiKey::class, $typed->apiKey);
        $this->assertSame(42, $typed->apiKey->id);
        $this->assertSame('mc-secret-12345', $typed->apiKey->key);
    }

    public function testApiKeyListResponseExtractsTypedEntries(): void
    {
        $body = [
            'data' => [
                ['id' => 1, 'key' => null],  // list endpoint typically omits the secret
                ['id' => 2, 'key' => null],
            ],
        ];

        $typed = SubAccountApiKeyListResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertNotNull($typed->apiKeys);
        $this->assertCount(2, $typed->apiKeys);
        $this->assertSame(1, $typed->apiKeys[0]->id);
        $this->assertNull($typed->apiKeys[0]->key);
    }

    public function testApiKeyListResponseRejectsWrongTypeIdInsideEntry(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'id' must be an int");
        SubAccountApiKeyListResponse::fromResponse(new Response(
            data: ['data' => [['id' => '42']]],
            statusCode: 200,
        ));
    }

    // ----- POST /sub-account/{handle}/smtp-password (create) -----

    public function testSmtpPasswordCreateResponseExtractsTypedFields(): void
    {
        $body = ['id' => 7, 'smtp_password' => 'p4ssw0rd', 'enabled' => true];

        $typed = SubAccountSmtpPasswordResponse::fromResponse(new Response(data: $body, statusCode: 201));

        $this->assertInstanceOf(SubAccountSmtpPassword::class, $typed->smtpPassword);
        $this->assertSame(7, $typed->smtpPassword->id);
        $this->assertSame('p4ssw0rd', $typed->smtpPassword->smtpPassword);
        $this->assertTrue($typed->smtpPassword->enabled);
    }

    public function testSmtpPasswordListResponseExtractsTypedEntries(): void
    {
        $body = [
            'data' => [
                ['id' => 1, 'enabled' => true],
                ['id' => 2, 'enabled' => false],
            ],
        ];

        $typed = SubAccountSmtpPasswordListResponse::fromResponse(new Response(data: $body, statusCode: 200));

        $this->assertNotNull($typed->smtpPasswords);
        $this->assertCount(2, $typed->smtpPasswords);
        $this->assertTrue($typed->smtpPasswords[0]->enabled);
        $this->assertFalse($typed->smtpPasswords[1]->enabled);
    }

    // ----- GET vs PUT /sub-account/{handle}/limit (different wire shapes) -----

    public function testLimitResponseHandlesGetShape(): void
    {
        // GET returns the Limit schema directly: {sends: int}
        $typed = SubAccountLimitResponse::fromResponse(new Response(
            data: ['sends' => 50000],
            statusCode: 200,
        ));

        $this->assertSame(50000, $typed->sends);
    }

    public function testLimitResponseHandlesPutWrapperShape(): void
    {
        // PUT returns the LimitUpdateResult schema: {limit: {sends: int}}
        $typed = SubAccountLimitResponse::fromResponse(new Response(
            data: ['limit' => ['sends' => 100000]],
            statusCode: 200,
        ));

        $this->assertSame(100000, $typed->sends);
    }

    public function testLimitResponseLeavesSendsNullWhenBodyEmpty(): void
    {
        $typed = SubAccountLimitResponse::fromResponse(new Response(data: [], statusCode: 200));
        $this->assertNull($typed->sends);
    }

    public function testLimitResponseRejectsWrongTypeSends(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'sends' must be an int");
        SubAccountLimitResponse::fromResponse(new Response(
            data: ['sends' => '50000'],
            statusCode: 200,
        ));
    }

    // ----- HTTP metadata + JSON round-trips -----

    public function testHttpMetadataPreservedAcrossSubAccountResponseClasses(): void
    {
        $raw = new Response(data: [], statusCode: 200, headers: ['X-Request-ID' => 'rid-sa']);

        $this->assertSame('rid-sa', SubAccountResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-sa', SubAccountListResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-sa', SubAccountApiKeyResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-sa', SubAccountApiKeyListResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-sa', SubAccountSmtpPasswordResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-sa', SubAccountSmtpPasswordListResponse::fromResponse($raw)->requestId());
        $this->assertSame('rid-sa', SubAccountLimitResponse::fromResponse($raw)->requestId());
    }

    public function testSubAccountDetailsJsonSerializeOmitsNullFields(): void
    {
        $d = new SubAccountDetails(handle: 't1', enabled: true);
        $this->assertSame(['handle' => 't1', 'enabled' => true], $d->jsonSerialize());
    }

    public function testSubAccountApiKeyJsonSerializeRoundTrip(): void
    {
        $k = new SubAccountApiKey(id: 42, key: 'mc-secret');
        $this->assertSame(['id' => 42, 'key' => 'mc-secret'], $k->jsonSerialize());
    }

    public function testSubAccountSmtpPasswordJsonSerializeRoundTrip(): void
    {
        $p = new SubAccountSmtpPassword(id: 1, smtpPassword: 'pw', enabled: true);
        $this->assertSame(
            ['id' => 1, 'smtp_password' => 'pw', 'enabled' => true],
            $p->jsonSerialize(),
        );
    }
}
