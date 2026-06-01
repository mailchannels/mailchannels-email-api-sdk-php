<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /webhook/public-key`. Spec schema: `Key`.
 *
 * `$id` is the key identifier referenced by the `keyid` parameter in
 * webhook signature headers; `$key` is the public key body (typically
 * base64-encoded Ed25519) used by
 * {@see \MailChannels\Webhook\Verifier} to verify inbound signatures.
 */
final class WebhookPublicKeyResponse extends Response
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
        public readonly ?string $id = null,
        public readonly ?string $key = null,
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
            id: ResponseValidator::asNullableString($body, 'id'),
            key: ResponseValidator::asNullableString($body, 'key'),
        );
    }
}
