<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `POST /webhook/validate`. Spec schema:
 * `WebhookValidationResults`.
 *
 * `$allPassed` is true when every enrolled webhook returned a 2xx HTTP
 * status during the validation test. `$results` carries the per-
 * webhook outcomes including the actual HTTP response MailChannels
 * received from each endpoint.
 *
 * ```php
 * $results = $client->webhooks->validate();
 * if ($results->allPassed !== true) {
 *     foreach ($results->results ?? [] as $r) {
 *         if ($r->result === 'failed') {
 *             echo $r->webhook, ' failed: HTTP ', $r->response?->status, "\n";
 *         }
 *     }
 * }
 * ```
 */
final class WebhookValidationResultsResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<WebhookValidationResult>|null $results
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?bool $allPassed = null,
        public readonly ?array $results = null,
    ) {
        parent::__construct($data, $statusCode, $headers, $rawBody);
    }

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->data;
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'results');
        $results = $rawList === null
            ? null
            : array_map(
                static fn (array $r): WebhookValidationResult => WebhookValidationResult::fromArray($r),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            allPassed: ResponseValidator::asNullableBool($body, 'all_passed'),
            results: $results,
        );
    }
}
