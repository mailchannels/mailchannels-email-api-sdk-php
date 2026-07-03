<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\CustomTrackingDomain;
use MailChannels\Response\CustomTrackingDomainListResponse;
use MailChannels\Response\CustomTrackingDomainResponse;
use PHPUnit\Framework\TestCase;

final class CustomTrackingDomainResponsesTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function registeredDomainBody(string $name = 'newsletter-clicks'): array
    {
        return [
            'name' => $name,
            'hostname' => 'click.example.com',
            'scope' => 'click',
            'status' => 'active',
            'created_at' => '2026-07-01T00:00:00Z',
        ];
    }

    public function testListResponseExtractsDomainsAndTotal(): void
    {
        $body = [
            'custom_tracking_domains' => [self::registeredDomainBody()],
            'total' => 1,
        ];

        $typed = new CustomTrackingDomainListResponse(new Response(data: $body, statusCode: 200));

        $this->assertSame(1, $typed->total);
        $this->assertNotNull($typed->customTrackingDomains);
        $this->assertCount(1, $typed->customTrackingDomains);
        $this->assertInstanceOf(CustomTrackingDomain::class, $typed->customTrackingDomains[0]);
        $this->assertSame('newsletter-clicks', $typed->customTrackingDomains[0]->name);
        $this->assertSame('click.example.com', $typed->customTrackingDomains[0]->hostname);
        $this->assertSame('click', $typed->customTrackingDomains[0]->scope);
        $this->assertSame('active', $typed->customTrackingDomains[0]->status);
        $this->assertSame('2026-07-01T00:00:00Z', $typed->customTrackingDomains[0]->createdAt);
    }

    public function testListResponseLeavesFieldsNullWhenBodyEmpty(): void
    {
        $typed = new CustomTrackingDomainListResponse(new Response(data: [], statusCode: 200));

        $this->assertNull($typed->customTrackingDomains);
        $this->assertNull($typed->total);
    }

    public function testListResponseRejectsDomainsNotAList(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'custom_tracking_domains' must be a list");
        new CustomTrackingDomainListResponse(new Response(
            data: ['custom_tracking_domains' => ['name' => 'oops']],
            statusCode: 200,
        ));
    }

    public function testDomainJsonSerializeOmitsNullFields(): void
    {
        $domain = new CustomTrackingDomain(name: 'newsletter-clicks', hostname: 'click.example.com');

        $this->assertSame(
            ['name' => 'newsletter-clicks', 'hostname' => 'click.example.com'],
            $domain->jsonSerialize(),
        );
    }

    public function testDomainJsonSerializeRoundTripsAllFields(): void
    {
        $domain = CustomTrackingDomain::fromArray(self::registeredDomainBody());

        $this->assertSame(self::registeredDomainBody(), $domain->jsonSerialize());
    }

    public function testResultResponseExtractsRegisteredDomainShape(): void
    {
        $typed = new CustomTrackingDomainResponse(
            new Response(data: self::registeredDomainBody(), statusCode: 201),
        );

        $this->assertSame('newsletter-clicks', $typed->name);
        $this->assertSame('click.example.com', $typed->hostname);
        $this->assertSame('click', $typed->scope);
        $this->assertSame('active', $typed->status);
        $this->assertSame('2026-07-01T00:00:00Z', $typed->createdAt);
        $this->assertNull($typed->instructions);
        $this->assertNull($typed->token);
    }

    public function testResultResponseExtractsPendingDnsSetupShape(): void
    {
        $body = [
            'instructions' => 'Add the TXT and CNAME records, then retry.',
            'token' => '550e8400-e29b-41d4-a716-446655440000',
            'txt_record_name' => '_mailchannels-verify.click.example.com',
            'txt_record_value' => '550e8400-e29b-41d4-a716-446655440000',
        ];

        $typed = new CustomTrackingDomainResponse(new Response(data: $body, statusCode: 202));

        $this->assertNull($typed->status);
        $this->assertNull($typed->name);
        $this->assertSame('Add the TXT and CNAME records, then retry.', $typed->instructions);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $typed->token);
        $this->assertSame('_mailchannels-verify.click.example.com', $typed->txtRecordName);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $typed->txtRecordValue);
    }

    /**
     * The DnsSetupRequired schema has no required fields at all — a
     * CNAME-only failure can return just `instructions`, or (per the spec)
     * even a body carrying only the TXT fields with `instructions` absent.
     * Unlike a stricter cross-field validator, this response class accepts
     * any subset of either shape's fields without throwing.
     */
    public function testResultResponseAcceptsPendingShapeWithoutInstructions(): void
    {
        $body = [
            'token' => '550e8400-e29b-41d4-a716-446655440000',
            'txt_record_name' => '_mailchannels-verify.click.example.com',
            'txt_record_value' => '550e8400-e29b-41d4-a716-446655440000',
        ];

        $typed = new CustomTrackingDomainResponse(new Response(data: $body, statusCode: 202));

        $this->assertNull($typed->instructions);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $typed->token);
    }

    public function testResultResponseRejectsWrongTypeStatus(): void
    {
        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'status' must be a string");
        new CustomTrackingDomainResponse(new Response(
            data: [...self::registeredDomainBody(), 'status' => 123],
            statusCode: 200,
        ));
    }

    public function testHttpMetadataPreservedAcrossAllResponseClasses(): void
    {
        $rawResponse = new Response(
            data: [],
            statusCode: 200,
            headers: ['X-Request-ID' => 'rid-custom-tracking'],
        );

        $list = new CustomTrackingDomainListResponse($rawResponse);
        $this->assertSame('rid-custom-tracking', $list->requestId());

        $result = new CustomTrackingDomainResponse($rawResponse);
        $this->assertSame('rid-custom-tracking', $result->requestId());
    }
}
