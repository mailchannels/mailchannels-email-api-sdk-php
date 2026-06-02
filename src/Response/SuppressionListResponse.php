<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Internal\ResponseValidator;
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
    /** @var list<SuppressionEntry>|null */
    public readonly ?array $suppressionList;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
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
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'suppression_list');
        $this->suppressionList = $rawList === null
            ? null
            : array_map(
                static fn (array $entry): SuppressionEntry => SuppressionEntry::fromArray($entry),
                $rawList,
            );
    }
}
