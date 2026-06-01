<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Suppressions::list()}
 * and {@see \MailChannels\Resource\Suppressions::create()}.
 *
 * Both endpoints return a `suppression_list` array of entries; this class
 * exposes them as `list<SuppressionEntry>` for typed iteration:
 *
 * ```php
 * foreach ($client->suppressions->list()->suppressionList ?? [] as $entry) {
 *     echo $entry->recipient, ' (', $entry->source, ")\n";
 * }
 * ```
 *
 * Inherits {@see Response}'s array-access, HTTP-metadata, and raw-body
 * helpers.
 */
final class SuppressionListResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<SuppressionEntry>|null $suppressionList
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $suppressionList = null,
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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'suppression_list');
        $entries = $rawList === null
            ? null
            : array_map(
                static fn (array $entry): SuppressionEntry => SuppressionEntry::fromArray($entry),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            suppressionList: $entries,
        );
    }
}
