<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit;

use MailChannels\Enum\CustomTrackingScope;
use MailChannels\Enum\CustomTrackingStatus;
use MailChannels\Enum\DkimAlgorithm;
use MailChannels\Enum\DkimUpdateStatus;
use MailChannels\Enum\MetricsInterval;
use MailChannels\Enum\MetricsSenderType;
use MailChannels\Enum\SuppressionDeleteSource;
use MailChannels\Enum\SuppressionSource;
use MailChannels\Enum\WebhookBatchStatus;
use MailChannels\Exception\InvalidRequestException;
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

    public function testMetricsSendersEncodesSenderTypePathSegment(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->metrics->senders('campaigns/weekly?x=1', startTime: '2026-05-01T00:00:00Z');

        $uri = $http->lastRequest()->getUri();
        $this->assertSame('/tx/v1/metrics/senders/campaigns%2Fweekly%3Fx%3D1', $uri->getPath());
        $this->assertStringContainsString('start_time=2026-05-01T00%3A00%3A00Z', $uri->getQuery());
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

    public function testDkimUpdateStatusEncodesPathSegments(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->updateStatus('example.com/path?x=1', 'selector #1/2', DkimUpdateStatus::Rotated);

        $uri = $http->lastRequest()->getUri();
        $this->assertSame(
            '/tx/v1/domains/example.com%2Fpath%3Fx%3D1/dkim-keys/selector%20%231%2F2',
            $uri->getPath(),
        );
        $this->assertSame('', $uri->getQuery());
    }

    public function testDkimListEncodesDomainBeforeAppendingQuery(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->list('example.com/path?x=1', selector: 'selector #1', status: 'active');

        $uri = $http->lastRequest()->getUri();
        $this->assertSame('/tx/v1/domains/example.com%2Fpath%3Fx%3D1/dkim-keys', $uri->getPath());
        $this->assertStringContainsString('selector=selector%20%231', $uri->getQuery());
        $this->assertStringContainsString('status=active', $uri->getQuery());
    }

    public function testQuestionMarkInPathSegmentDoesNotLeakIntoQuery(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->list('example.com?injected=1', status: 'active');

        $uri = $http->lastRequest()->getUri();
        $this->assertSame('/tx/v1/domains/example.com%3Finjected%3D1/dkim-keys', $uri->getPath());
        $this->assertStringNotContainsString('injected', $uri->getQuery());
        $this->assertStringContainsString('status=active', $uri->getQuery());
    }

    public function testDkimRotate(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->rotate('example.com', 'mc20260101', 'mc20260601');
        $this->assertSame(['new_key' => ['selector' => 'mc20260601']], $http->lastJson());
    }

    public function testDkimRotateEncodesExistingPathSegmentsOnly(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->dkim->rotate('example.com/path?x=1', 'selector #1/2', 'new/selector?raw=1');

        $uri = $http->lastRequest()->getUri();
        $this->assertSame(
            '/tx/v1/domains/example.com%2Fpath%3Fx%3D1/dkim-keys/selector%20%231%2F2/rotate',
            $uri->getPath(),
        );
        $this->assertSame(['new_key' => ['selector' => 'new/selector?raw=1']], $http->lastJson());
    }

    public function testCustomTrackingDomainsListAcceptsFilters(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['custom_tracking_domains' => [], 'total' => 0]));
        $client = Fixtures::client($http);
        $client->customTrackingDomains->list(
            name: 'newsletter-clicks',
            status: CustomTrackingStatus::Active,
            scope: CustomTrackingScope::Click,
            limit: 10,
            offset: 0,
        );

        $query = $http->lastRequest()->getUri()->getQuery();
        $this->assertStringContainsString('name=newsletter-clicks', $query);
        $this->assertStringContainsString('status=active', $query);
        $this->assertStringContainsString('scope=click', $query);
        $this->assertStringContainsString('limit=10', $query);
        $this->assertStringContainsString('offset=0', $query);
        $this->assertStringEndsWith('/custom-tracking-domains', $http->lastRequest()->getUri()->getPath());
    }

    public function testCustomTrackingDomainsCreate(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(201, [
            'name' => 'newsletter-clicks',
            'hostname' => 'click.example.com',
            'scope' => 'click',
            'status' => 'active',
            'created_at' => '2026-07-01T00:00:00Z',
        ]));
        $client = Fixtures::client($http);

        $result = $client->customTrackingDomains->create(
            name: 'newsletter-clicks',
            hostname: 'click.example.com',
            scope: CustomTrackingScope::Click,
        );

        $this->assertSame(
            ['name' => 'newsletter-clicks', 'hostname' => 'click.example.com', 'scope' => 'click'],
            $http->lastJson(),
        );
        $this->assertSame('POST', $http->lastRequest()->getMethod());
        $this->assertStringEndsWith('/custom-tracking-domains', (string) $http->lastRequest()->getUri());
        $this->assertSame('active', $result->status);
    }

    public function testCustomTrackingDomainsCreateAcceptsPendingDnsSetupResponse(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, [
            'instructions' => 'Add the TXT and CNAME records, then retry.',
            'token' => '550e8400-e29b-41d4-a716-446655440000',
            'txt_record_name' => '_mailchannels-verify.click.example.com',
            'txt_record_value' => '550e8400-e29b-41d4-a716-446655440000',
        ]));
        $client = Fixtures::client($http);

        $result = $client->customTrackingDomains->create(
            name: 'newsletter-clicks',
            hostname: 'click.example.com',
            scope: CustomTrackingScope::Click,
        );

        $this->assertNull($result->status);
        $this->assertSame('Add the TXT and CNAME records, then retry.', $result->instructions);
        $this->assertSame(202, $result->statusCode);
    }

    public function testCustomTrackingDomainsCreateRaisesInvalidRequestForIncompleteDns(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(422, [
            'instructions' => 'DNS verification incomplete; check the TXT record.',
        ]));
        $client = Fixtures::client($http);

        try {
            $client->customTrackingDomains->create(
                name: 'newsletter-clicks',
                hostname: 'click.example.com',
                scope: CustomTrackingScope::Click,
            );
            $this->fail('Expected InvalidRequestException.');
        } catch (InvalidRequestException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertIsArray($e->getResponseBody());
            $this->assertSame(
                'DNS verification incomplete; check the TXT record.',
                $e->getResponseBody()['instructions'],
            );
        }
    }

    public function testCustomTrackingDomainsUpdateUsesPatchAndAcceptsPendingResponse(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, [
            'instructions' => 'Re-verification required.',
        ]));
        $client = Fixtures::client($http);

        $result = $client->customTrackingDomains->update(
            'click.example.com',
            CustomTrackingScope::Click,
            status: CustomTrackingStatus::Active,
        );

        $this->assertSame('PATCH', $http->lastRequest()->getMethod());
        $this->assertSame(['status' => 'active'], $http->lastJson());
        $this->assertStringEndsWith('/custom-tracking-domains/click.example.com/click', (string) $http->lastRequest()->getUri());
        $this->assertNull($result->status);
        $this->assertSame('Re-verification required.', $result->instructions);
    }

    public function testCustomTrackingDomainsUpdateEncodesPathSegments(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, [
            'name' => 'n', 'hostname' => 'h', 'scope' => 'click', 'status' => 'active', 'created_at' => 'now',
        ]));
        $client = Fixtures::client($http);
        $client->customTrackingDomains->update('example.com/path?x=1', 'click', name: 'renamed');

        $uri = $http->lastRequest()->getUri();
        $this->assertSame(
            '/tx/v1/custom-tracking-domains/example.com%2Fpath%3Fx%3D1/click',
            $uri->getPath(),
        );
        $this->assertSame('', $uri->getQuery());
    }

    public function testCustomTrackingDomainsDeleteUsesDocumentedPath(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(204, null));
        $client = Fixtures::client($http);
        $client->customTrackingDomains->delete('click.example.com', CustomTrackingScope::Click);

        $this->assertSame('DELETE', $http->lastRequest()->getMethod());
        $this->assertStringEndsWith('/custom-tracking-domains/click.example.com/click', (string) $http->lastRequest()->getUri());
    }

    public function testCustomTrackingDomainsDeleteEncodesPathSegments(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(204, null));
        $client = Fixtures::client($http);
        $client->customTrackingDomains->delete('example.com/path?x=1', 'click');

        $uri = $http->lastRequest()->getUri();
        $this->assertSame(
            '/tx/v1/custom-tracking-domains/example.com%2Fpath%3Fx%3D1/click',
            $uri->getPath(),
        );
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
        $client->suppressions->delete('user+tag@example.com/path?x=1', SuppressionDeleteSource::All);

        $uri = $http->lastRequest()->getUri();
        $this->assertSame(
            '/tx/v1/suppression-list/recipients/user%2Btag%40example.com%2Fpath%3Fx%3D1',
            $uri->getPath(),
        );
        $this->assertSame('source=all', $uri->getQuery());
    }

    public function testSubAccountApiKeyDeleteEncodesKeyIdPathSegment(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->subAccounts->apiKeys->delete('tenant42', 'key #1/2%3');

        $this->assertSame(
            '/tx/v1/sub-account/tenant42/api-key/key%20%231%2F2%253',
            $http->lastRequest()->getUri()->getPath(),
        );
    }

    public function testSubAccountSmtpPasswordDeleteEncodesPasswordIdPathSegment(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, []));
        $client = Fixtures::client($http);
        $client->subAccounts->smtpPasswords->delete('tenant42', 'smtp #1/2');

        $this->assertSame(
            '/tx/v1/sub-account/tenant42/smtp-password/smtp%20%231%2F2',
            $http->lastRequest()->getUri()->getPath(),
        );
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

    public function testWebhooksResendBatchPostsToCorrectUrl(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);
        $client->webhooks->resendBatch(42);
        $req = $http->lastRequest();
        $this->assertTrue($req->hasHeader('X-Api-Key'));
        $this->assertFalse($req->hasHeader('X-Customer-Handle'));
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
