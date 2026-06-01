<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Emails::send()}.
 *
 * Covers both the normal `/send` success body (`message_id` + optional
 * per-recipient `results`) and the dry-run body (a `data` list of rendered
 * messages). All typed fields are nullable because the API omits a given
 * field whenever it doesn't apply to that response shape.
 *
 * Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
 * helpers — `$response['message_id']`, `$response->statusCode`,
 * `$response->headers`, and `$response->requestId()` all keep working.
 * `requestId()` is overridden here to check the response body's
 * `request_id` field before falling back to header inspection.
 */
final class SendResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<array<string, mixed>>|null $results
     * @param list<array<string, mixed>>|null $dryRunData
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?string $messageId = null,
        public readonly ?array $results = null,
        public readonly ?array $dryRunData = null,
    ) {
        parent::__construct($data, $statusCode, $headers, $rawBody);
    }

    /**
     * Build a SendResponse from a generic {@see Response}, validating field
     * types as it goes.
     *
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are not errors — they're nulled out
     *     so callers can branch on `dryRunData !== null` etc.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->data;
        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            messageId: ResponseValidator::asNullableString($body, 'message_id'),
            results: ResponseValidator::asNullableListOfArrays($body, 'results'),
            dryRunData: ResponseValidator::asNullableListOfArrays($body, 'data'),
        );
    }

    public function requestId(): ?string
    {
        $bodyValue = $this->data['request_id'] ?? null;
        if (is_string($bodyValue) && $bodyValue !== '') {
            return $bodyValue;
        }
        return parent::requestId();
    }
}
