<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /sub-account/{handle}/smtp-password`. The
 * API returns a bare JSON array of `SMTPPassword` entries;
 * {@see \MailChannels\Transport} wraps bare arrays under the `data`
 * key during decoding.
 *
 * The list endpoint typically omits the raw `$smtpPassword` material
 * on each entry; only `$id` and `$enabled` are reliably populated.
 */
final class SubAccountSmtpPasswordListResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<SubAccountSmtpPassword>|null $smtpPasswords
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $smtpPasswords = null,
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
        $smtpPasswords = $rawList === null
            ? null
            : array_map(
                static fn (array $p): SubAccountSmtpPassword => SubAccountSmtpPassword::fromArray($p),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            smtpPasswords: $smtpPasswords,
        );
    }
}
