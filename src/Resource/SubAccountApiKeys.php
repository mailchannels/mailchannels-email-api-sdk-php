<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use MailChannels\Response;
use MailChannels\Response\SubAccountApiKeyListResponse;
use MailChannels\Response\SubAccountApiKeyResponse;

final class SubAccountApiKeys extends AbstractResource
{
    public function create(string $handle): SubAccountApiKeyResponse
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Creating sub-account API key', ['handle' => $handle]);
        return SubAccountApiKeyResponse::fromResponse(
            $this->request('POST', "/sub-account/{$handle}/api-key"),
        );
    }

    public function list(string $handle): SubAccountApiKeyListResponse
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Listing sub-account API keys', ['handle' => $handle]);
        return SubAccountApiKeyListResponse::fromResponse(
            $this->request('GET', "/sub-account/{$handle}/api-key"),
        );
    }

    public function delete(string $handle, string|int $keyId): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Deleting sub-account API key', ['handle' => $handle, 'key_id' => $keyId]);
        return $this->request('DELETE', "/sub-account/{$handle}/api-key/{$keyId}");
    }
}
