<?php

namespace MailChannels\Tests\Unit\Plugins\Symfony\Transport;

use MailChannels\Plugins\Symfony\Transport\MailChannelsApiTransport;
use MailChannels\Plugins\Symfony\Transport\MailChannelsTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient as SymfonyMockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;

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

    public function testUsesInjectedSymfonyHttpClientInsteadOfDiscovery(): void
    {
        $body = json_encode([
            'request_id' => 'req-1',
            'results' => [['index' => 0, 'status' => 'sent']],
        ]);
        $symfonyHttpClient = new SymfonyMockHttpClient([new MockResponse((string) $body, ['http_code' => 202])]);

        $factory = new MailChannelsTransportFactory(client: $symfonyHttpClient);
        $transport = $factory->create(Dsn::fromString('mailchannels+api://my-key@default'));

        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Hi');

        $transport->send($email);

        // If this were 0, the transport fell back to php-http/discovery instead of
        // routing the request through the app's configured Symfony HttpClient.
        $this->assertSame(1, $symfonyHttpClient->getRequestsCount());
    }
}
