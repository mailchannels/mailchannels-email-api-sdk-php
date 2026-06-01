<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Dkim::create()}
 * for `POST /domains/{domain}/dkim-keys`.
 *
 * The API returns the created `DKIMKeyInfo` object directly as the
 * response body (no wrapper key in the JSON). This class exposes that
 * info as `$key` for ergonomic typed access while keeping the body
 * itself array-accessible via the inherited {@see Response} surface.
 *
 * ```php
 * $created = $client->dkim->create('example.com', 'mc1', algorithm: DkimAlgorithm::Rsa);
 * echo $created->key->selector;
 * foreach ($created->key->dkimDnsRecords ?? [] as $record) {
 *     echo "publish ", $record->name, ' ', $record->type, ' ', $record->value, "\n";
 * }
 * ```
 */
final class DkimKeyResponse extends Response
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
        public readonly ?DkimKeyInfo $key = null,
    ) {
        parent::__construct($data, $statusCode, $headers, $rawBody);
    }

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. An empty body leaves `$key` null.
     */
    public static function fromResponse(Response $response): self
    {
        $body = $response->data;
        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            key: $body === [] ? null : DkimKeyInfo::fromArray($body),
        );
    }
}
