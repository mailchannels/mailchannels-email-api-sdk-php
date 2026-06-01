<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Dkim::list()}.
 * Wraps a list of typed {@see DkimKeyInfo} entries.
 *
 * ```php
 * $list = $client->dkim->list('example.com', includeDnsRecord: true);
 * foreach ($list->keys ?? [] as $key) {
 *     echo $key->selector, ' (', $key->status, ")\n";
 *     foreach ($key->dkimDnsRecords ?? [] as $record) {
 *         echo "  publish ", $record->name, ' ', $record->type, ' ', $record->value, "\n";
 *     }
 * }
 * ```
 */
final class DkimKeyListResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<DkimKeyInfo>|null $keys
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $keys = null,
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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'keys');
        $keys = $rawList === null
            ? null
            : array_map(
                static fn (array $k): DkimKeyInfo => DkimKeyInfo::fromArray($k),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            keys: $keys,
        );
    }
}
