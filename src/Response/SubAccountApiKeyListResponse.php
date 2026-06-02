<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /sub-account/{handle}/api-key`. The API
 * returns a bare JSON array of `APIKey` entries;
 * {@see \MailChannels\Transport} wraps bare arrays under the `data`
 * key during decoding.
 *
 * The list endpoint typically omits the secret `$key` material on
 * each entry; only `$id` is reliably populated.
 */
final class SubAccountApiKeyListResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<SubAccountApiKey>|null $apiKeys
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $apiKeys = null,
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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'data');
        $apiKeys = $rawList === null
            ? null
            : array_map(
                static fn (array $k): SubAccountApiKey => SubAccountApiKey::fromArray($k),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            apiKeys: $apiKeys,
        );
    }
}
