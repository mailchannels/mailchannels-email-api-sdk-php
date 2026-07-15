<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\UsageResponse;
use PHPUnit\Framework\TestCase;

final class UsageResponseTest extends TestCase
{
    public function testFromResponseExtractsTypedFields(): void
    {
        $response = new Response(
            data: [
                'total_usage' => 1234,
                'period_start_date' => '2026-06-01',
                'period_end_date' => '2026-06-30',
                'monthly_limit' => 10000,
            ],
            statusCode: 200,
        );

        $typed = new UsageResponse($response);

        $this->assertSame(1234, $typed->totalUsage);
        $this->assertSame('2026-06-01', $typed->periodStartDate);
        $this->assertSame('2026-06-30', $typed->periodEndDate);
        $this->assertSame(10000, $typed->monthlyLimit);
    }

    public function testFromResponseLeavesFieldsNullWhenBodyEmpty(): void
    {
        $typed = new UsageResponse(new Response(data: [], statusCode: 200));

        $this->assertNull($typed->totalUsage);
        $this->assertNull($typed->periodStartDate);
        $this->assertNull($typed->periodEndDate);
        $this->assertNull($typed->monthlyLimit);
    }

    public function testFromResponseRejectsWrongTypeTotalUsage(): void
    {
        $response = new Response(
            data: ['total_usage' => '1234'],  // string instead of int
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'total_usage' must be an int");
        new UsageResponse($response);
    }

    public function testFromResponseRejectsWrongTypeMonthlyLimit(): void
    {
        $response = new Response(
            data: ['monthly_limit' => '10000'],  // string instead of int
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'monthly_limit' must be an int");
        new UsageResponse($response);
    }

    public function testFromResponseRejectsWrongTypePeriodStartDate(): void
    {
        $response = new Response(
            data: ['period_start_date' => ['2026-06-01']],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'period_start_date' must be a string");
        new UsageResponse($response);
    }

    public function testPreservesHttpMetadata(): void
    {
        $response = new Response(
            data: ['total_usage' => 42],
            statusCode: 200,
            headers: ['X-Request-ID' => 'rid-usage', 'Content-Type' => 'application/json'],
            rawBody: '{"total_usage":42}',
        );

        $typed = new UsageResponse($response);

        $this->assertSame(200, $typed->statusCode);
        $this->assertSame('rid-usage', $typed->requestId());
        $this->assertSame('{"total_usage":42}', $typed->rawBody);
    }

    public function testInheritedArrayAccessKeepsWorking(): void
    {
        $response = new Response(
            data: ['total_usage' => 99, 'extra_unmodeled_field' => 'kept'],
            statusCode: 200,
        );

        $typed = new UsageResponse($response);

        $this->assertSame(99, $typed->totalUsage);
        $this->assertSame(99, $typed['total_usage']);
        $this->assertSame('kept', $typed['extra_unmodeled_field']);
    }
}
