<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use MailChannels\Exception\ValidationException;
use MailChannels\Response;

/**
 * `POST /check-domain` — checks DKIM, SPF, sender-domain DNS, and Domain
 * Lockdown status for a domain.
 */
final class CheckDomain extends AbstractResource
{
    /**
     * @param list<array{domain?: string, selector?: string, private_key?: string}>|null $dkimSettings
     */
    public function check(string $domain, ?string $senderId = null, ?array $dkimSettings = null): Response
    {
        if ($domain === '') {
            throw new ValidationException('Domain is required.', errorCode: 'InvalidDomainCheck');
        }
        $payload = self::compact([
            'domain' => $domain,
            'sender_id' => $senderId,
            'dkim_settings' => $dkimSettings,
        ]);
        $this->client->config()->logger->info('Checking domain configuration', ['domain' => $domain]);
        return $this->request('POST', '/check-domain', $payload);
    }
}
