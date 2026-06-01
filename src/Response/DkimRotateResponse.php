<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response returned by {@see \MailChannels\Resource\Dkim::rotate()}.
 * Exposes both the freshly-minted selector (`$newKey`) and the just-
 * rotated old selector (`$rotatedKey`), with the new key carrying the
 * `dkimDnsRecords` you need to publish before sending traffic under the
 * new selector.
 */
final class DkimRotateResponse extends Response
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
        public readonly ?DkimKeyInfo $newKey = null,
        public readonly ?DkimKeyInfo $rotatedKey = null,
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
        $newKeyData = ResponseValidator::asNullableAssocArray($body, 'new_key');
        $rotatedKeyData = ResponseValidator::asNullableAssocArray($body, 'rotated_key');

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            newKey: $newKeyData === null ? null : DkimKeyInfo::fromArray($newKeyData),
            rotatedKey: $rotatedKeyData === null ? null : DkimKeyInfo::fromArray($rotatedKeyData),
        );
    }
}
