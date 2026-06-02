<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Internal\ResponseValidator;
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
    public readonly ?string $messageId;

    /** @var list<array<string, mixed>>|null */
    public readonly ?array $results;

    /** @var list<array<string, mixed>>|null */
    public readonly ?array $dryRunData;

    /**
     * Build a SendResponse from a generic {@see Response}, validating
     * field types as it goes.
     *
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are not errors — they're nulled out
     *     so callers can branch on `dryRunData !== null` etc.
     */
    public function __construct(Response $response)
    {
        $body = $response->data;
        parent::__construct(
            $response->data,
            $response->statusCode,
            $response->headers,
            $response->rawBody,
        );
        $this->messageId = ResponseValidator::asNullableString($body, 'message_id');
        $this->results = ResponseValidator::asNullableListOfArrays($body, 'results');
        $this->dryRunData = ResponseValidator::asNullableListOfArrays($body, 'data');
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
