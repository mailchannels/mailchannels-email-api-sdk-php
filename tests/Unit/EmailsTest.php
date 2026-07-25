<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit;

use MailChannels\Exception\ValidationException;
use MailChannels\Message\Attachment;
use MailChannels\Message\Content;
use MailChannels\Message\EmailAddress;
use MailChannels\Message\EmailParams;
use MailChannels\Message\Personalization;
use MailChannels\Tests\Support\Fixtures;
use MailChannels\Tests\Support\MockHttpClient;
use PHPUnit\Framework\TestCase;

final class EmailsTest extends TestCase
{
    public function testSendBuildsExpectedPayloadFromShortcutArray(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => ['email' => 'sender@example.com', 'name' => 'Priya Patel'],
            'to' => 'recipient@example.net',
            'subject' => 'Hi',
            'text' => 'hello',
        ]);

        $req = $http->lastRequest();
        $this->assertSame('POST', $req->getMethod());
        $this->assertStringEndsWith('/tx/v1/send', (string) $req->getUri());
        $this->assertSame('application/json', $req->getHeaderLine('Content-Type'));

        $payload = $http->lastJson();
        $this->assertSame(['email' => 'sender@example.com', 'name' => 'Priya Patel'], $payload['from']);
        $this->assertSame('Hi', $payload['subject']);
        $this->assertSame([['type' => 'text/plain', 'value' => 'hello']], $payload['content']);
        $this->assertSame(
            [['to' => [['email' => 'recipient@example.net']]]],
            $payload['personalizations'],
        );
    }

    public function testSendDryRunAddsQueryParameter(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(200, ['data' => []]));
        $client = Fixtures::client($http);

        $client->emails->send(
            params: ['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 's', 'text' => 't'],
            dryRun: true,
        );

        $this->assertStringContainsString('dry-run=true', $http->lastRequest()->getUri()->getQuery());
    }

    public function testSendWithEmailParamsObject(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $params = new EmailParams(
            from: new EmailAddress('sender@example.com'),
            subject: 'Welcome',
            personalizations: [new Personalization(to: ['recipient@example.net'])],
            content: [Content::plain('Hi there')],
            transactional: true,
        );

        $client->emails->send($params);

        $payload = $http->lastJson();
        $this->assertSame('Welcome', $payload['subject']);
        $this->assertTrue($payload['transactional']);
        $this->assertSame([['type' => 'text/plain', 'value' => 'Hi there']], $payload['content']);
    }

    public function testEmailParamsEmitsTrackingAndUnsubscribeSettings(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $params = new EmailParams(
            from: new EmailAddress('sender@example.com'),
            subject: 'Tracked',
            personalizations: [new Personalization(to: ['recipient@example.net'])],
            content: [Content::plain('Hi there')],
            trackingSettings: [
                'click_tracking' => ['enable' => true, 'custom_domain_name' => 'newsletter-clicks'],
            ],
            unsubscribeSettings: ['custom_domain_name' => 'newsletter-unsubscribe'],
        );

        $client->emails->send($params);

        $payload = $http->lastJson();
        $this->assertSame(
            ['click_tracking' => ['enable' => true, 'custom_domain_name' => 'newsletter-clicks']],
            $payload['tracking_settings'],
        );
        $this->assertSame(
            ['custom_domain_name' => 'newsletter-unsubscribe'],
            $payload['unsubscribe_settings'],
        );
    }

    public function testQueueHitsSendAsyncEndpoint(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->queue([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 'queued',
            'text' => 'body',
        ]);

        $this->assertStringEndsWith('/tx/v1/send-async', (string) $http->lastRequest()->getUri());
    }

    public function testSendingWithoutFromRaisesValidationException(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send(['to' => 'a@b.com', 'subject' => 's', 'text' => 't']);
    }

    public function testSendingWithoutContentRaisesValidationException(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send(['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 's']);
    }

    public function testReservedHeaderRaises(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'headers' => ['From' => 'imposter@b.com'],
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function reservedHeaderProvider(): array
    {
        return [
            'authentication-results' => ['Authentication-Results'],
            'content-transfer-encoding' => ['Content-Transfer-Encoding'],
            'received' => ['Received'],
            'dkim-signature' => ['DKIM-Signature'],
            'message-id' => ['Message-ID'],
        ];
    }

    /** @dataProvider reservedHeaderProvider */
    public function testReservedHeaderListIsComplete(string $headerName): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'headers' => [$headerName => 'whatever'],
        ]);
    }

    public function testCampaignIdAndEnvelopeFromAndTrackingPassThrough(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'envelope_from' => 'bounces@example.com',
            'to' => 'recipient@example.net',
            'subject' => 'tracked',
            'text' => 't',
            'campaign_id' => 'spring-2026',
            'tracking_settings' => [
                'open_tracking' => ['enable' => true],
                'click_tracking' => ['enable' => true, 'custom_domain_name' => 'newsletter-clicks'],
            ],
            'unsubscribe_settings' => [
                'custom_domain_name' => 'newsletter-unsubscribe',
            ],
        ]);

        $payload = $http->lastJson();
        $this->assertSame('spring-2026', $payload['campaign_id']);
        $this->assertSame(['email' => 'bounces@example.com'], $payload['envelope_from']);
        $this->assertSame(
            [
                'open_tracking' => ['enable' => true],
                'click_tracking' => ['enable' => true, 'custom_domain_name' => 'newsletter-clicks'],
            ],
            $payload['tracking_settings'],
        );
        $this->assertSame(
            ['custom_domain_name' => 'newsletter-unsubscribe'],
            $payload['unsubscribe_settings'],
        );
    }

    public function testReservedHeaderInsidePersonalizationArrayRaises(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'sender@x.com',
            'subject' => 's',
            'text' => 't',
            'personalizations' => [
                [
                    'to' => [['email' => 'a@x.com']],
                    'headers' => ['Subject' => 'overridden via header'],
                ],
            ],
        ]);
    }

    public function testValidCustomHeaderInsidePersonalizationArrayPasses(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);
        $client->emails->send([
            'from' => 'sender@x.com',
            'subject' => 's',
            'text' => 't',
            'personalizations' => [
                [
                    'to' => [['email' => 'a@x.com']],
                    'headers' => ['X-Custom-Tag' => 'campaign'],
                ],
            ],
        ]);
        $payload = $http->lastJson();
        $this->assertIsArray($payload['personalizations']);
        $first = $payload['personalizations'][0] ?? null;
        $this->assertIsArray($first);
        $this->assertSame(['X-Custom-Tag' => 'campaign'], $first['headers']);
    }

    public function testEmailParamsCarriesNewFields(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $params = new EmailParams(
            from: new EmailAddress('sender@example.com'),
            subject: 'tracked',
            personalizations: [new Personalization(to: ['recipient@example.net'])],
            content: [Content::plain('hi')],
            envelopeFrom: new EmailAddress('bounces@example.com'),
            campaignId: 'spring-2026',
            trackingSettings: ['open_tracking' => ['enable' => true]],
        );

        $client->emails->send($params);

        $payload = $http->lastJson();
        $this->assertSame('spring-2026', $payload['campaign_id']);
        $this->assertSame(['email' => 'bounces@example.com'], $payload['envelope_from']);
        $this->assertSame(['open_tracking' => ['enable' => true]], $payload['tracking_settings']);
    }

    public function testAttachmentFromBytesEncodesBase64(): void
    {
        $att = Attachment::fromBytes('hello world', 'note.txt');
        $arr = $att->toArray();
        $this->assertSame('note.txt', $arr['filename']);
        $this->assertSame(base64_encode('hello world'), $arr['content']);
        $this->assertSame('text/plain', $arr['type']);
    }

    public function testEmailAddressRejectsBadShape(): void
    {
        $this->expectException(ValidationException::class);
        new EmailAddress('not-an-email');
    }

    public function testCcAndBccArePropagated(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);
        $client->emails->send([
            'from' => 'sender@x.com',
            'to' => ['a@x.com', 'b@x.com'],
            'cc' => 'c@x.com',
            'bcc' => ['d@x.com'],
            'subject' => 'hi',
            'text' => 't',
        ]);
        $payload = $http->lastJson();
        $this->assertIsArray($payload['personalizations']);
        $personalization = $payload['personalizations'][0] ?? null;
        $this->assertIsArray($personalization);
        $this->assertIsArray($personalization['to']);
        $this->assertCount(2, $personalization['to']);
        $this->assertSame([['email' => 'c@x.com']], $personalization['cc']);
        $this->assertSame([['email' => 'd@x.com']], $personalization['bcc']);
    }

    public function testSendingWithoutSubjectRaises(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send(['from' => 'a@b.com', 'to' => 'c@d.com', 'text' => 't']);
    }

    public function testSendingWithEmptySubjectRaises(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => '',
            'text' => 't',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function fromAliasProvider(): array
    {
        return [
            'from_email' => ['from_email'],
            'from_address' => ['from_address'],
            'from_field' => ['from_field'],
        ];
    }

    /** @dataProvider fromAliasProvider */
    public function testFromAddressAcceptsAlternativeKeys(string $key): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            $key => 'sender@example.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
        ]);

        $this->assertSame(['email' => 'sender@example.com'], $http->lastJson()['from']);
    }

    public function testReplyToIsPropagated(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'reply_to' => ['email' => 'replies@example.com', 'name' => 'Support'],
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
        ]);

        $this->assertSame(
            ['email' => 'replies@example.com', 'name' => 'Support'],
            $http->lastJson()['reply_to'],
        );
    }

    public function testCustomerDkimFieldsPassThroughFromArrayForm(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'dkim_domain' => 'example.com',
            'dkim_selector' => 'sel1',
            'dkim_private_key' => 'fake-key',
            'transactional' => true,
        ]);

        $payload = $http->lastJson();
        $this->assertSame('example.com', $payload['dkim_domain']);
        $this->assertSame('sel1', $payload['dkim_selector']);
        $this->assertSame('fake-key', $payload['dkim_private_key']);
        $this->assertTrue($payload['transactional']);
    }

    public function testContentArrayFormWithExplicitListAndTemplateType(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'content' => [
                ['type' => 'text/plain', 'value' => 'hello {{ name }}', 'template_type' => 'mustache'],
                ['type' => 'text/html', 'value' => '<p>hello</p>'],
            ],
        ]);

        $content = $http->lastJson()['content'] ?? null;
        $this->assertIsArray($content);
        $this->assertCount(2, $content);
        $this->assertIsArray($content[0]);
        $this->assertIsArray($content[1]);
        $this->assertSame('mustache', $content[0]['template_type']);
        $this->assertArrayNotHasKey('template_type', $content[1]);
    }

    public function testContentArrayFormAcceptsContentObjectsInList(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'content' => [Content::plain('text'), Content::html('<p>hi</p>')],
        ]);

        $this->assertSame(
            [
                ['type' => 'text/plain', 'value' => 'text'],
                ['type' => 'text/html', 'value' => '<p>hi</p>'],
            ],
            $http->lastJson()['content'],
        );
    }

    public function testContentMustBeIterable(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'content' => 'not-iterable',
        ]);
    }

    public function testContentEntriesRequireTypeAndValue(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'content' => [['type' => 'text/plain']],
        ]);
    }

    public function testPersonalizationsAcceptsListOfObjects(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'subject' => 's',
            'text' => 't',
            'personalizations' => [
                new Personalization(to: ['a@example.com']),
                new Personalization(to: ['b@example.com'], dynamicTemplateData: ['name' => 'Bob']),
            ],
        ]);

        $personalizations = $http->lastJson()['personalizations'] ?? null;
        $this->assertIsArray($personalizations);
        $this->assertCount(2, $personalizations);
        $this->assertIsArray($personalizations[1]);
        $this->assertSame(['name' => 'Bob'], $personalizations[1]['dynamic_template_data']);
    }

    public function testPersonalizationsMustBeIterable(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'subject' => 's',
            'text' => 't',
            'personalizations' => 'not-a-list',
        ]);
    }

    public function testEmptyPersonalizationsArrayRaises(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'subject' => 's',
            'text' => 't',
            'personalizations' => [],
        ]);
    }

    public function testAttachmentObjectInArrayForm(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'attachments' => [Attachment::fromBytes('hello', 'note.txt')],
        ]);

        $attachments = $http->lastJson()['attachments'] ?? null;
        $this->assertIsArray($attachments);
        $this->assertCount(1, $attachments);
        $this->assertIsArray($attachments[0]);
        $this->assertSame('note.txt', $attachments[0]['filename']);
        $this->assertSame(base64_encode('hello'), $attachments[0]['content']);
    }

    public function testAttachmentArrayShapeRequiresContentAndFilename(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'attachments' => [['content' => 'xxx']],
        ]);
    }

    public function testAttachmentsMustBeIterable(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'attachments' => 'not-a-list',
        ]);
    }

    public function testHeadersMustBeArray(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'headers' => 'string-not-array',
        ]);
    }

    public function testHeadersRequireStringValues(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'headers' => ['X-Tag' => 42],
        ]);
    }

    public function testAddressListAcceptsEmailAddressObject(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'to' => new EmailAddress('recipient@example.com', 'Recipient'),
            'subject' => 's',
            'text' => 't',
        ]);

        $personalizations = $http->lastJson()['personalizations'] ?? null;
        $this->assertIsArray($personalizations);
        $first = $personalizations[0] ?? null;
        $this->assertIsArray($first);
        $this->assertSame(
            [['email' => 'recipient@example.com', 'name' => 'Recipient']],
            $first['to'],
        );
    }

    public function testAddressListAcceptsListOfEmailAddressObjects(): void
    {
        $http = (new MockHttpClient())->queue(Fixtures::jsonResponse(202, []));
        $client = Fixtures::client($http);

        $client->emails->send([
            'from' => 'sender@example.com',
            'to' => [
                new EmailAddress('a@example.com', 'Alice'),
                new EmailAddress('b@example.com'),
            ],
            'subject' => 's',
            'text' => 't',
        ]);

        $personalizations = $http->lastJson()['personalizations'] ?? null;
        $this->assertIsArray($personalizations);
        $first = $personalizations[0] ?? null;
        $this->assertIsArray($first);
        $this->assertSame(
            [
                ['email' => 'a@example.com', 'name' => 'Alice'],
                ['email' => 'b@example.com'],
            ],
            $first['to'],
        );
    }

    public function testAddressListRejectsScalarTypes(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 42,
            'subject' => 's',
            'text' => 't',
        ]);
    }

    public function testTrackingSettingsMustBeArray(): void
    {
        $http = new MockHttpClient();
        $client = Fixtures::client($http);
        $this->expectException(ValidationException::class);
        $client->emails->send([
            'from' => 'a@b.com',
            'to' => 'c@d.com',
            'subject' => 's',
            'text' => 't',
            'tracking_settings' => 'not-array',
        ]);
    }
}
