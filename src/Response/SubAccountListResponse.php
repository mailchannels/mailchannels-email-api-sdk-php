<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /sub-account`. The API returns a bare JSON
 * array of `SubAccountDetails` entries; {@see \MailChannels\Transport}
 * wraps bare arrays under the `data` key during decoding, and this
 * class extracts the typed list from there.
 */
final class SubAccountListResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<SubAccountDetails>|null $subAccounts
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $subAccounts = null,
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
        $subAccounts = $rawList === null
            ? null
            : array_map(
                static fn (array $s): SubAccountDetails => SubAccountDetails::fromArray($s),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            subAccounts: $subAccounts,
        );
    }
}
