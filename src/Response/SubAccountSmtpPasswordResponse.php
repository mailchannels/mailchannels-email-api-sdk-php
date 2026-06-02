<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `POST /sub-account/{handle}/smtp-password`. The
 * API returns a single `SMTPPassword` object directly as the response
 * body. Exposes that as `$smtpPassword`.
 *
 * `$smtpPassword->smtpPassword` is the raw credential returned by the
 * create endpoint — treat as a secret.
 */
final class SubAccountSmtpPasswordResponse extends Response
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
        public readonly ?SubAccountSmtpPassword $smtpPassword = null,
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
            smtpPassword: $body === [] ? null : SubAccountSmtpPassword::fromArray($body),
        );
    }
}
