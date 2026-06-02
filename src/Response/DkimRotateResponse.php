<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Internal\ResponseValidator;
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
    public readonly ?DkimKeyInfo $newKey;
    public readonly ?DkimKeyInfo $rotatedKey;

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
        $newKeyData = ResponseValidator::asNullableAssocArray($body, 'new_key');
        $rotatedKeyData = ResponseValidator::asNullableAssocArray($body, 'rotated_key');
        $this->newKey = $newKeyData === null ? null : DkimKeyInfo::fromArray($newKeyData);
        $this->rotatedKey = $rotatedKeyData === null ? null : DkimKeyInfo::fromArray($rotatedKeyData);
    }
}
