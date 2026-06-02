<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit;

use MailChannels\Enum\DkimAlgorithm;
use MailChannels\Enum\DkimUpdateStatus;
use MailChannels\Enum\MetricsInterval;
use MailChannels\Enum\MetricsSenderType;
use MailChannels\Enum\SuppressionDeleteSource;
use MailChannels\Enum\SuppressionSource;
use MailChannels\Enum\WebhookBatchStatus;
use MailChannels\Exception\ValidationException;
use MailChannels\Response;
use MailChannels\Tests\Support\Fixtures;
use MailChannels\Tests\Support\MockHttpClient;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
{
    public function testSubAccountsCreate(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['handle' => 'tenant42']));
        $client = Fixtures::client($http);
        $client->subAccounts->create(companyName: 'ACME', handle: 'tenant42');

        $req = $http->lastRequest();
        $this->assertSame('POST', $req->getMethod());
        $this->assertStringEndsWith('/sub-account', (string) $req->getUri());
        $this->assertSame(['company_name' => 'ACME', 'handle' => 'tenant42'], $http->lastJson());
    }

    public function testSubAccountsCreateRequiresCompanyName(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $this->expectException(\MailChannels\Exception\ValidationException::class);
        $client->subAccounts->create(companyName: '');
    }

    public function testSubAccountsCreateRejectsInvalidHandle(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $this->expectException(\MailChannels\Exception\ValidationException::class);
        $client->subAccounts->create(companyName: 'ACME', handle: 'Tenant-42');
    }

    public function testSubAccountsCreateRejectsShortHandle(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $this->expectException(\MailChannels\Exception\ValidationException::class);
        $client->subAccounts->create(companyName: 'ACME', handle: 'ab');
    }

    public function testSubAccountOperationsRejectInvalidHandles(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $operations = [
            'retrieveUsage' => fn (): Response => $client->subAccounts->retrieveUsage('Tenant-42'),
            'suspend' => fn (): Response => $client->subAccounts->suspend('Tenant-42'),
            'activate' => fn (): Response => $client->subAccounts->activate('Tenant-42'),
            'delete' => fn (): Response => $client->subAccounts->delete('Tenant-42'),
            'apiKeysCreate' => fn (): Response => $client->subAccounts->apiKeys->create('Tenant-42'),
            'apiKeysList' => fn (): Response => $client->subAccounts->apiKeys->list('Tenant-42'),
            'apiKeysDelete' => fn (): Response => $client->subAccounts->apiKeys->delete('Tenant-42', 1),
            'smtpPasswordsCreate' => fn (): Response => $client->subAccounts->smtpPasswords->create('Tenant-42'),
            'smtpPasswordsList' => fn (): Response => $client->subAccounts->smtpPasswords->list('Tenant-42'),
            'smtpPasswordsDelete' => fn (): Response => $client->subAccounts->smtpPasswords->delete('Tenant-42', 1),
            'limitsSet' => fn (): Response => $client->subAccounts->limits->set('Tenant-42', sends: 1),
            'limitsRetrieve' => fn (): Response => $client->subAccounts->limits->retrieve('Tenant-42'),
            'limitsDelete' => fn (): Response => $client->subAccounts->limits->delete('Tenant-42'),
        ];

        foreach ($operations as $name => $operation) {
            try {
                $operation();
                $this->fail("{$name} accepted an invalid handle.");
            } catch (ValidationException $e) {
                $this->assertSame('InvalidSubAccountHandle', $e->getErrorCode());
            }
        }
    }

    public function testSubAccountsCreateRejectsShortCompanyName(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $this->expectException(\MailChannels\Exception\ValidationException::class);
        $client->subAccounts->create(companyName: 'AB');
    }

    public function testSubAccountsLimitsSetRejectsNegativeSends(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $this->expectException(\MailChannels\Exception\ValidationException::class);
        $client->subAccounts->limits->set('tenant42', sends: -1);
    }

    public function testSubAccountsCreateAcceptsLowercaseAlphanumericHandle(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['handle' => 'abc123']));
        $client = Fixtures::client($http);
        $client->subAccounts->create(companyName: 'ACME', handle: 'abc123');
        $this->assertSame(['company_name' => 'ACME', 'handle' => 'abc123'], $http->lastJson());
    }

    public function testSubAccountsNestedApiKeysCreate(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['id' => 1, 'key' => 'mc-test-secret']));
        $client = Fixtures::client($http);
        $client->subAccounts->apiKeys->create('tenant42');
        $this->assertStringEndsWith('/sub-account/tenant42/api-key', (string) $http->lastRequest()->getUri());
    }

    public function testSubAccountsLimitsSet(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['sends' => 50000]));
        $client = Fixtures::client($http);
        $client->subAccounts->limits->set('tenant42', sends: 50000);
        $req = $http->lastRequest();
        $this->assertSame('PUT', $req->getMethod());
        $this->assertSame(['sends' => 50000], $http->lastJson());
    }

    public function testMetricsEngagementSerializesQuery(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['data' => []]));
        $client = Fixtures::client($http);
        $client->metrics->engagement(
            startTime: '2026-05-01T00:00:00Z',
            endTime: '2026-05-08T00:00:00Z',
            interval: MetricsInterval::Day,
        );
        $query = $http->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('start_time=2026-05-01T00%3A00%3A00Z', $query);
        $this->assertStringContainsString('interval=day', $query);
    }

    public function testMetricsSendersPathIncludesType(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->metrics->senders(MetricsSenderType::Campaigns);
        $this->assertStringContainsString('/metrics/senders/campaigns', (string) $http->lastRequest()->getUri());
    }

    public function testCheckDomain(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['ok' => true]));
        $client = Fixtures::client($http);
        $client->checkDomain->check('example.com');
        $this->assertSame(['domain' => 'example.com'], $http->lastJson());
    }

    public function testDkimCreate(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->create('example.com', selector: 'mc20260514', algorithm: DkimAlgorithm::Rsa);
        $this->assertSame(
            ['selector' => 'mc20260514', 'algorithm' => 'rsa'],
            $http->lastJson(),
        );
        $this->assertStringEndsWith('/domains/example.com/dkim-keys', (string) $http->lastRequest()->getUri());
    }

    public function testDkimUpdateStatusUsesPatch(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->updateStatus('example.com', 'mc20260514', DkimUpdateStatus::Retired);
        $this->assertSame('PATCH', $http->lastRequest()->getMethod());
        $this->assertSame(['status' => 'retired'], $http->lastJson());
    }

    public function testDkimRotate(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->rotate('example.com', 'mc20260101', 'mc20260601');
        $this->assertSame(['new_key' => ['selector' => 'mc20260601']], $http->lastJson());
    }

    public function testSuppressionsListAcceptsEnum(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['data' => []]));
        $client = Fixtures::client($http);
        $client->suppressions->list(source: SuppressionSource::SpamComplaint, limit: 25);
        $query = $http->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('source=spam_complaint', $query);
        $this->assertStringContainsString('limit=25', $query);
    }

    public function testSuppressionsCreatePayload(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->suppressions->create([
            [
                'recipient' => 'bouncer@example.net',
                'suppression_types' => ['transactional', 'non-transactional'],
                'notes' => 'Hard-bounced 2026-05-14',
            ],
        ]);
        $this->assertSame(
            [
                'suppression_entries' => [
                    [
                        'recipient' => 'bouncer@example.net',
                        'suppression_types' => ['transactional', 'non-transactional'],
                        'notes' => 'Hard-bounced 2026-05-14',
                    ],
                ],
            ],
            $http->lastJson(),
        );
    }

    public function testSuppressionsDeleteEncodesRecipientPathSegment(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->suppressions->delete('user/name+tag@example.com', SuppressionDeleteSource::All);

        $uri = (string) $http->lastRequest()->getUri();
        $this->assertStringContainsString('/suppression-list/recipients/user%2Fname%2Btag%40example.com', $uri);
        $this->assertStringContainsString('source=all', $uri);
    }

    public function testWebhookEndpointMustBeAUrl(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $this->expectException(\MailChannels\Exception\ValidationException::class);
        $client->webhooks->create('not a url');
    }

    public function testWebhookEndpointMustUseHttpOrHttps(): void
    {
        $client = Fixtures::client(new MockHttpClient());
        $this->expectException(\MailChannels\Exception\ValidationException::class);
        $client->webhooks->create('ftp://example.com/hook');
    }

    public function testWebhookEndpointAcceptsHttps(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->webhooks->create('https://hooks.example.com/mc');
        $this->assertStringContainsString(
            'endpoint=https%3A%2F%2Fhooks.example.com%2Fmc',
            $http->lastRequest()->getUri()->getQuery(),
        );
    }

    public function testWebhooksDeleteAllUsesDeleteMethod(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->webhooks->deleteAll();

        $req = $http->lastRequest();
        $this->assertSame('DELETE', $req->getMethod());
        $this->assertStringEndsWith('/webhook', (string) $req->getUri());
    }

    public function testWebhooksBatchesJoinsStatusList(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->webhooks->batches(statuses: [WebhookBatchStatus::ClientError, WebhookBatchStatus::ServerError]);
        $this->assertStringContainsString('statuses=4xx%2C5xx', $http->lastRequest()->getUri()->getQuery());
    }

    public function testWebhooksPublicKeyDoesNotSendApiKey(): void
    {
        $http = (new MockHttpClient())->queue(
            Fixtures::jsonResponse(200, ['id' => 'key-42', 'key' => 'base64-ed25519-key'])
        );
        $client = Fixtures::client($http);
        $response = $client->webhooks->publicKey('key-42');
        $this->assertFalse($http->lastRequest()->hasHeader('X-Api-Key'));
        $this->assertStringContainsString('id=key-42', $http->lastRequest()->getUri()->getQuery());
        $this->assertSame('base64-ed25519-key', $response->get('key'));
    }

    public function testWebhooksResendBatchUsesCustomerHandleHeaderInsteadOfApiKey(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);
        $client->webhooks->resendBatch(42, 'tenant-42');
        $req = $http->lastRequest();
        $this->assertFalse($req->hasHeader('X-Api-Key'));
        $this->assertSame('tenant-42', $req->getHeaderLine('X-Customer-Handle'));
        $this->assertStringEndsWith('/webhook-batch/42/resend', (string) $req->getUri());
    }

    public function testUsageRetrieve(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['sends' => 12]));
        $client = Fixtures::client($http);
        $response = $client->usage->retrieve();
        $this->assertSame(12, $response['sends']);
    }
}
