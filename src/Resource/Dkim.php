<?php

declare(strict_types=1);

namespace MailChannels\Resource;

use MailChannels\Enum\DkimAlgorithm;
use MailChannels\Enum\DkimKeyStatus;
use MailChannels\Enum\DkimUpdateStatus;
use MailChannels\Response;

final class Dkim extends AbstractResource
{
    public function create(
        string $domain,
        string $selector,
        DkimAlgorithm|string|null $algorithm = null,
        ?int $keyLength = null,
    ): Response {
        $payload = self::compact([
            'selector' => $selector,
            'algorithm' => $algorithm instanceof DkimAlgorithm ? $algorithm->value : $algorithm,
            'key_length' => $keyLength,
        ]);
        $this->client->config()->logger->info('Creating DKIM key pair', ['domain' => $domain, 'selector' => $selector]);
        return $this->request('POST', "/domains/{$domain}/dkim-keys", $payload);
    }

    public function list(
        string $domain,
        ?string $selector = null,
        DkimKeyStatus|string|null $status = null,
        ?int $offset = null,
        ?int $limit = null,
        ?bool $includeDnsRecord = null,
    ): Response {
        $query = self::compactOrNull([
            'selector' => $selector,
            'status' => $status instanceof DkimKeyStatus ? $status->value : $status,
            'offset' => $offset,
            'limit' => $limit,
            'include_dns_record' => $includeDnsRecord,
        ]);
        return $this->request('GET', "/domains/{$domain}/dkim-keys", null, $query);
    }

    public function updateStatus(string $domain, string $selector, DkimUpdateStatus|string $status): Response
    {
        $statusValue = $status instanceof DkimUpdateStatus ? $status->value : $status;
        $this->client->config()->logger->info('Updating DKIM key status', [
            'domain' => $domain,
            'selector' => $selector,
            'status' => $statusValue,
        ]);
        return $this->request('PATCH', "/domains/{$domain}/dkim-keys/{$selector}", ['status' => $statusValue]);
    }

    public function rotate(string $domain, string $selector, string $newSelector): Response
    {
        $this->client->config()->logger->info('Rotating DKIM key', [
            'domain' => $domain,
            'selector' => $selector,
            'new_selector' => $newSelector,
        ]);
        return $this->request(
            'POST',
            "/domains/{$domain}/dkim-keys/{$selector}/rotate",
            ['new_key' => ['selector' => $newSelector]],
        );
    }
}
