<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Message;

use MailChannels\Exception\ValidationException;
use MailChannels\Message\EmailAddress;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function invalidEmailProvider(): array
    {
        return [
            'empty string' => [''],
            'starts with at' => ['@example.com'],
            'ends with at' => ['user@'],
            'missing at' => ['user-at-example.com'],
        ];
    }

    /** @dataProvider invalidEmailProvider */
    public function testConstructorRejectsInvalidEmails(string $value): void
    {
        $this->expectException(ValidationException::class);
        new EmailAddress($value);
    }

    public function testFromReturnsExistingInstanceUnchanged(): void
    {
        $original = new EmailAddress('a@example.com', 'Alice');

        $this->assertSame($original, EmailAddress::from($original));
    }

    public function testFromAcceptsBareString(): void
    {
        $addr = EmailAddress::from('a@example.com');

        $this->assertSame('a@example.com', $addr->email);
        $this->assertNull($addr->name);
    }

    public function testFromAcceptsArrayWithEmailAndName(): void
    {
        $addr = EmailAddress::from(['email' => 'b@example.com', 'name' => 'Bob']);

        $this->assertSame('b@example.com', $addr->email);
        $this->assertSame('Bob', $addr->name);
    }

    public function testFromArrayWithoutEmailKeyRaises(): void
    {
        $this->expectException(ValidationException::class);
        EmailAddress::from(['name' => 'no email']);
    }

    public function testFromArrayWithNonStringNameIgnoresName(): void
    {
        $addr = EmailAddress::from(['email' => 'c@example.com', 'name' => 42]);

        $this->assertSame('c@example.com', $addr->email);
        $this->assertNull($addr->name);
    }

    public function testFromRejectsUnsupportedScalarType(): void
    {
        $this->expectException(ValidationException::class);
        EmailAddress::from(42);
    }

    public function testListFromConvertsIterable(): void
    {
        $list = EmailAddress::listFrom([
            'a@example.com',
            new EmailAddress('b@example.com', 'Bob'),
            ['email' => 'c@example.com'],
        ]);

        $this->assertCount(3, $list);
        $this->assertSame('a@example.com', $list[0]->email);
        $this->assertSame('Bob', $list[1]->name);
        $this->assertSame('c@example.com', $list[2]->email);
    }

    public function testToArrayOmitsNullName(): void
    {
        $this->assertSame(['email' => 'a@example.com'], (new EmailAddress('a@example.com'))->toArray());
    }

    public function testToArrayOmitsEmptyStringName(): void
    {
        $this->assertSame(['email' => 'a@example.com'], (new EmailAddress('a@example.com', ''))->toArray());
    }

    public function testToArrayIncludesNameWhenSet(): void
    {
        $this->assertSame(
            ['email' => 'a@example.com', 'name' => 'Alice'],
            (new EmailAddress('a@example.com', 'Alice'))->toArray(),
        );
    }

    public function testJsonSerializeMatchesToArray(): void
    {
        $addr = new EmailAddress('a@example.com', 'Alice');

        $this->assertSame($addr->toArray(), $addr->jsonSerialize());
    }

    public function testStringableWithoutName(): void
    {
        $this->assertSame('a@example.com', (string) new EmailAddress('a@example.com'));
    }

    public function testStringableWithName(): void
    {
        $this->assertSame(
            'Alice <a@example.com>',
            (string) new EmailAddress('a@example.com', 'Alice'),
        );
    }
}
