<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `POST /sub-account/{handle}/api-key`. The API
 * returns a single `APIKey` object directly as the response body.
 * Exposes that as `$apiKey`.
 *
 * `$apiKey->key` contains the raw key string returned by the create
 * endpoint — treat it as a credential. The list endpoint omits the
 * secret material.
 */
final class SubAccountApiKeyResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?SubAccountApiKey $apiKey = null,
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
        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            apiKey: $body === [] ? null : SubAccountApiKey::fromArray($body),
        );
    }
}
