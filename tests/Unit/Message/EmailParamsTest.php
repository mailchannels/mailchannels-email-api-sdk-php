<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Message;

use Generator;
use MailChannels\Exception\ValidationException;
use MailChannels\Message\Attachment;
use MailChannels\Message\Content;
use MailChannels\Message\EmailAddress;
use MailChannels\Message\EmailParams;
use MailChannels\Message\Personalization;
use PHPUnit\Framework\TestCase;

final class EmailParamsTest extends TestCase
{
    public function testRequiresAtLeastOnePersonalization(): void
    {
        $this->expectException(ValidationException::class);
        new EmailParams(
            from: new EmailAddress('a@b.com'),
            subject: 's',
            personalizations: [],
            content: [Content::plain('t')],
        );
    }

    public function testRequiresAtLeastOneContentPart(): void
    {
        $this->expectException(ValidationException::class);
        new EmailParams(
            from: new EmailAddress('a@b.com'),
            subject: 's',
            personalizations: [new Personalization(to: ['c@d.com'])],
            content: [],
        );
    }

    public function testRequiresNonEmptySubject(): void
    {
        $this->expectException(ValidationException::class);
        new EmailParams(
            from: new EmailAddress('a@b.com'),
            subject: '',
            personalizations: [new Personalization(to: ['c@d.com'])],
            content: [Content::plain('t')],
        );
    }

    public function testAcceptsGeneratorsForPersonalizationsAndContent(): void
    {
        $personalizationGen = (function (): Generator {
            yield new Personalization(to: ['a@example.com']);
            yield new Personalization(to: ['b@example.com']);
        })();

        $contentGen = (function (): Generator {
            yield Content::plain('text body');
            yield Content::html('<p>html body</p>');
        })();

        $params = new EmailParams(
            from: new EmailAddress('sender@example.com'),
            subject: 'hi',
            personalizations: $personalizationGen,
            content: $contentGen,
        );

        $this->assertCount(2, $params->personalizations);
        $this->assertCount(2, $params->content);
    }

    public function testToPayloadIncludesEveryPopulatedOptionalField(): void
    {
        $params = new EmailParams(
            from: new EmailAddress('sender@example.com'),
            subject: 'hi',
            personalizations: [new Personalization(to: ['recipient@example.com'])],
            content: [Content::plain('body')],
            replyTo: new EmailAddress('reply@example.com'),
            envelopeFrom: new EmailAddress('bounces@example.com'),
            headers: ['X-Tag' => 'campaign'],
            attachments: [Attachment::fromBytes('bytes', 'note.txt')],
            transactional: true,
            campaignId: 'spring-2026',
            trackingSettings: ['open_tracking' => ['enable' => true]],
            dkimDomain: 'example.com',
            dkimPrivateKey: 'key',
            dkimSelector: 'sel',
        );

        $payload = $params->toPayload();

        $this->assertSame(['email' => 'reply@example.com'], $payload['reply_to']);
        $this->assertSame(['email' => 'bounces@example.com'], $payload['envelope_from']);
        $this->assertSame(['X-Tag' => 'campaign'], $payload['headers']);
        $this->assertIsArray($payload['attachments']);
        $this->assertCount(1, $payload['attachments']);
        $this->assertTrue($payload['transactional']);
        $this->assertSame('spring-2026', $payload['campaign_id']);
        $this->assertSame(['open_tracking' => ['enable' => true]], $payload['tracking_settings']);
        $this->assertSame('example.com', $payload['dkim_domain']);
        $this->assertSame('key', $payload['dkim_private_key']);
        $this->assertSame('sel', $payload['dkim_selector']);
    }

    public function testToPayloadOmitsAllNullOptionalFields(): void
    {
        $params = new EmailParams(
            from: new EmailAddress('sender@example.com'),
            subject: 'hi',
            personalizations: [new Personalization(to: ['recipient@example.com'])],
            content: [Content::plain('body')],
        );

        $payload = $params->toPayload();

        $this->assertArrayNotHasKey('reply_to', $payload);
        $this->assertArrayNotHasKey('envelope_from', $payload);
        $this->assertArrayNotHasKey('headers', $payload);
        $this->assertArrayNotHasKey('attachments', $payload);
        $this->assertArrayNotHasKey('transactional', $payload);
        $this->assertArrayNotHasKey('campaign_id', $payload);
        $this->assertArrayNotHasKey('tracking_settings', $payload);
        $this->assertArrayNotHasKey('dkim_domain', $payload);
        $this->assertArrayNotHasKey('dkim_private_key', $payload);
        $this->assertArrayNotHasKey('dkim_selector', $payload);
    }
}
