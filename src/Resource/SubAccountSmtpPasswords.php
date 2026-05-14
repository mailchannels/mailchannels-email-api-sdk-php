<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use MailChannels\Response;

final class SubAccountSmtpPasswords extends AbstractResource
{
    public function create(string $handle): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Creating sub-account SMTP password', ['handle' => $handle]);
        return $this->request('POST', "/sub-account/{$handle}/smtp-password");
    }

    public function list(string $handle): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Listing sub-account SMTP passwords', ['handle' => $handle]);
        return $this->request('GET', "/sub-account/{$handle}/smtp-password");
    }

    public function delete(string $handle, string|int $passwordId): Response
    {
        SubAccounts::validateHandle($handle);
        $this->client->config()->logger->info('Deleting sub-account SMTP password', ['handle' => $handle, 'password_id' => $passwordId]);
        return $this->request('DELETE', "/sub-account/{$handle}/smtp-password/{$passwordId}");
    }
}
