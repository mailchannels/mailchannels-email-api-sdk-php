<?php

declare(strict_types=1);

namespace MailChannels\Response;

use MailChannels\Exception\ResponseValidationException;
use MailChannels\Response;

/**
 * Typed response for `GET /webhook`. The API returns a bare JSON array
 * of {@see WebhookSubscription} entries (no envelope); the SDK's
 * {@see \MailChannels\Transport} wraps bare arrays under the `data`
 * key in the decoded body, and this class extracts the typed list from
 * there.
 *
 * ```php
 * foreach ($client->webhooks->list()->webhooks ?? [] as $sub) {
 *     echo $sub->webhook, "\n";
 * }
 * ```
 */
final class WebhookListResponse extends Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     * @param list<WebhookSubscription>|null $webhooks
     */
    public function __construct(
        array $data,
        int $statusCode,
        array $headers = [],
        ?string $rawBody = null,
        public readonly ?array $webhooks = null,
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
        // Transport wraps bare-array responses as ['data' => [...]].
        $rawList = ResponseValidator::asNullableListOfArrays($body, 'data');
        $subs = $rawList === null
            ? null
            : array_map(
                static fn (array $s): WebhookSubscription => WebhookSubscription::fromArray($s),
                $rawList,
            );

        return new self(
            data: $body,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->rawBody,
            webhooks: $subs,
        );
    }
}
