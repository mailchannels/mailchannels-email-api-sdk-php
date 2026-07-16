<?php

namespace MailChannels\Tests\Unit\Plugins\Symfony\Transport;

use MailChannels\Plugins\Symfony\Transport\MailChannelsApiTransport;
use MailChannels\Plugins\Symfony\Transport\MailChannelsTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\Dsn;

class MailChannelsTransportFactoryTest extends TestCase
{
    public function testCreatesTransportFromMinimalDsn(): void
    {
        $factory = new MailChannelsTransportFactory();

        $transport = $factory->create(Dsn::fromString('mailchannels+api://my-key@default'));

        $this->assertInstanceOf(MailChannelsApiTransport::class, $transport);
        $this->assertSame('mailchannels+api://default', (string) $transport);
    }

    public function testCreatesTransportWithHostOverride(): void
    {
        $factory = new MailChannelsTransportFactory();

        $transport = $factory->create(Dsn::fromString(
            'mailchannels+api://my-key@sandbox.example.com'
        ));

        $this->assertSame('mailchannels+api://https://sandbox.example.com', (string) $transport);
    }

    public function testCreatesTransportWithHostAndPortOverride(): void
    {
        $factory = new MailChannelsTransportFactory();

        $transport = $factory->create(Dsn::fromString(
            'mailchannels+api://my-key@sandbox.example.com:8443'
        ));

        $this->assertSame('mailchannels+api://https://sandbox.example.com:8443', (string) $transport);
    }

    public function testRejectsUnsupportedScheme(): void
    {
        $factory = new MailChannelsTransportFactory();

        $this->expectException(UnsupportedSchemeException::class);

        $factory->create(Dsn::fromString('smtp://my-key@default'));
    }
}
