<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\CheckDomain::check()}.
 *
 * The `/check-domain` endpoint returns deeply nested verdict data — DKIM
 * results (one per selector), SPF status, Domain Lockdown verdict, and
 * sender-domain DNS (A/MX) checks. The SDK exposes the top-level grouping
 * as `$checkResults` (an associative array preserving the API's shape)
 * and `$references` (documentation URLs the API points at). Drill into
 * the verdicts via array access:
 *
 * ```php
 * $r = $client->checkDomain->check('example.com');
 * echo $r->checkResults['spf']['verdict'];                // 'passed' | 'failed'
 * echo $r->checkResults['domain_lockdown']['verdict'];
 * foreach ($r->checkResults['dkim'] ?? [] as $dkim) {
 *     echo $dkim['dkim_selector'], ': ', $dkim['verdict'], "\n";
 * }
 * ```
 *
 * Both fields are nullable. The nested shapes are not further typed at
 * this layer; callers that want stronger typing should construct their
 * own value objects from the arrays.
 */
final class CheckDomainResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param array<string, mixed>|null $checkResults
     * @param list<string>|null $references
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $checkResults = null,
        public readonly ?array $references = null,
    ) {
        parent::__construct($data, $statusCode, $headers, $rawBody);
    }

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->data;
        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            checkResults: ResponseValidator::asNullableAssocArray($body, 'check_results'),
            references: ResponseValidator::asNullableListOfStrings($body, 'references'),
        );
    }
}
