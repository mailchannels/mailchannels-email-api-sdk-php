<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Message;

use MailChannels\Exception\ValidationException;
use MailChannels\Message\EmailAddress;
use MailChannels\Message\Personalization;
use PHPUnit\Framework\TestCase;

final class PersonalizationTest extends TestCase
{
    public function testRequiresAtLeastOneToRecipient(): void
    {
        $this->expectException(ValidationException::class);
        new Personalization(to: []);
    }

    public function testCcAndBccAcceptIterablesOfMixedShapes(): void
    {
        $p = new Personalization(
            to: ['a@example.com'],
            cc: [new EmailAddress('cc@example.com', 'CC')],
            bcc: [['email' => 'bcc@example.com']],
        );

        $this->assertCount(1, $p->to);
        $this->assertNotNull($p->cc);
        $this->assertNotNull($p->bcc);
        $this->assertSame('cc@example.com', $p->cc[0]->email);
        $this->assertSame('bcc@example.com', $p->bcc[0]->email);
    }

    public function testNullCcAndBccRemainNullAfterConstruction(): void
    {
        $p = new Personalization(to: ['a@example.com']);

        $this->assertNull($p->cc);
        $this->assertNull($p->bcc);
    }

    public function testToArrayOmitsAllOptionalFieldsWhenUnset(): void
    {
        $p = new Personalization(to: ['a@example.com']);

        $this->assertSame(
            ['to' => [['email' => 'a@example.com']]],
            $p->toArray(),
        );
    }

    public function testToArrayIncludesEveryPopulatedOptionalField(): void
    {
        $p = new Personalization(
            to: ['a@example.com'],
            cc: ['c@example.com'],
            bcc: ['b@example.com'],
            subject: 'override',
            from: new EmailAddress('from@example.com'),
            replyTo: new EmailAddress('reply@example.com'),
            headers: ['X-Tag' => 'campaign'],
            substitutions: ['{first}' => 'Alice'],
            dynamicTemplateData: ['name' => 'Alice'],
            dkimDomain: 'example.com',
            dkimPrivateKey: 'key',
            dkimSelector: 'sel',
        );

        $payload = $p->toArray();
        $this->assertSame([['email' => 'c@example.com']], $payload['cc']);
        $this->assertSame([['email' => 'b@example.com']], $payload['bcc']);
        $this->assertSame('override', $payload['subject']);
        $this->assertSame(['email' => 'from@example.com'], $payload['from']);
        $this->assertSame(['email' => 'reply@example.com'], $payload['reply_to']);
        $this->assertSame(['X-Tag' => 'campaign'], $payload['headers']);
        $this->assertSame(['{first}' => 'Alice'], $payload['substitutions']);
        $this->assertSame(['name' => 'Alice'], $payload['dynamic_template_data']);
        $this->assertSame('example.com', $payload['dkim_domain']);
        $this->assertSame('key', $payload['dkim_private_key']);
        $this->assertSame('sel', $payload['dkim_selector']);
    }

    public function testJsonSerializeMatchesToArray(): void
    {
        $p = new Personalization(to: ['a@example.com'], subject: 'hi');

        $this->assertSame($p->toArray(), $p->jsonSerialize());
    }
}
