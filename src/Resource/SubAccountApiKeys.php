<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use MailChannels\Response;

final class SubAccountApiKeys extends AbstractResource
{
    public function create(string $handle): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Creating sub-account API key', ['handle' => $handle]);
        return $this->request('POST', "/sub-account/{$handle}/api-key");
    }

    public function list(string $handle): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Listing sub-account API keys', ['handle' => $handle]);
        return $this->request('GET', "/sub-account/{$handle}/api-key");
    }

    public function delete(string $handle, string|int $keyId): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Deleting sub-account API key', ['handle' => $handle, 'key_id' => $keyId]);
        return $this->request('DELETE', "/sub-account/{$handle}/api-key/{$keyId}");
    }
}
