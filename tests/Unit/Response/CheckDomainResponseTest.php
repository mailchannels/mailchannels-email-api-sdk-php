<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;
use MailChannels\Response\CheckDomainResponse;
use PHPUnit\Framework\TestCase;

final class CheckDomainResponseTest extends TestCase
{
    public function testFromResponseExtractsCheckResultsAndReferences(): void
    {
        $body = [
            'check_results' => [
                'spf' => ['verdict' => 'passed', 'spfRecord' => 'v=spf1 ...'],
                'domain_lockdown' => ['verdict' => 'passed'],
                'dkim' => [
                    ['dkim_selector' => 'mc1', 'verdict' => 'passed'],
                    ['dkim_selector' => 'mc2', 'verdict' => 'failed', 'reason' => 'missing-record'],
                ],
                'sender_domain' => [
                    'verdict' => 'passed',
                    'a' => ['verdict' => 'passed'],
                    'mx' => ['verdict' => 'passed'],
                ],
            ],
            'references' => [
                'https://docs.mailchannels.net/spf',
                'https://docs.mailchannels.net/dkim',
            ],
        ];

        $typed = new CheckDomainResponse(new Response(data: $body, statusCode: 200));

        $this->assertIsArray($typed->checkResults);
        $spf = $typed->checkResults['spf'] ?? null;
        $this->assertIsArray($spf);
        $this->assertSame('passed', $spf['verdict']);

        $dkim = $typed->checkResults['dkim'] ?? null;
        $this->assertIsArray($dkim);
        $this->assertCount(2, $dkim);
        $second = $dkim[1] ?? null;
        $this->assertIsArray($second);
        $this->assertSame('mc2', $second['dkim_selector']);

        $this->assertSame(
            ['https://docs.mailchannels.net/spf', 'https://docs.mailchannels.net/dkim'],
            $typed->references,
        );
    }

    public function testFromResponseLeavesFieldsNullWhenBodyEmpty(): void
    {
        $typed = new CheckDomainResponse(new Response(data: [], statusCode: 200));

        $this->assertNull($typed->checkResults);
        $this->assertNull($typed->references);
    }

    public function testFromResponseRejectsCheckResultsAsList(): void
    {
        $response = new Response(
            data: ['check_results' => ['spf', 'dkim']],  // sequential list, not assoc
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'check_results' must be an associative array");
        new CheckDomainResponse($response);
    }

    public function testFromResponseRejectsReferencesContainingNonString(): void
    {
        $response = new Response(
            data: ['references' => ['https://docs.mailchannels.net/spf', 42]],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'references'[1] must be a string");
        new CheckDomainResponse($response);
    }

    public function testFromResponseRejectsReferencesAsAssoc(): void
    {
        $response = new Response(
            data: ['references' => ['key' => 'https://example.com']],
            statusCode: 200,
        );

        $this->expectException(ResponseValidationException::class);
        $this->expectExceptionMessage("Response field 'references' must be a list");
        new CheckDomainResponse($response);
    }

    public function testPreservesHttpMetadata(): void
    {
        $response = new Response(
            data: ['check_results' => ['spf' => ['verdict' => 'passed']]],
            statusCode: 200,
            headers: ['X-Request-ID' => 'rid-check', 'Content-Type' => 'application/json'],
            rawBody: '{"check_results":{"spf":{"verdict":"passed"}}}',
        );

        $typed = new CheckDomainResponse($response);

        $this->assertSame(200, $typed->statusCode);
        $this->assertSame('rid-check', $typed->requestId());
        $this->assertSame('{"check_results":{"spf":{"verdict":"passed"}}}', $typed->rawBody);
    }
}
