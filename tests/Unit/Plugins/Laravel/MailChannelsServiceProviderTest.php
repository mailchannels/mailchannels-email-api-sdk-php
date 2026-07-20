<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit\Plugins\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use MailChannels\Plugins\Laravel\MailChannelsServiceProvider;
use MailChannels\Plugins\Symfony\Transport\MailChannelsApiTransport;
use MailChannels\Tests\Support\MockLaravelApplication;
use MailChannels\Tests\Support\MockLaravelConfigRepository;
use MailChannels\Tests\Support\RecordingLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;

class MailChannelsServiceProviderTest extends TestCase
{
    private MockLaravelApplication $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new MockLaravelApplication();
        $this->app->instance('config', new MockLaravelConfigRepository());
        $this->app->singleton('mail.manager', static fn (Application $app) => new MailManager($app));

        Facade::setFacadeApplication($this->app);
        Facade::clearResolvedInstances();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    private function bootProvider(): void
    {
        (new MailChannelsServiceProvider($this->app))->boot();
    }

    /** @param array<string, mixed> $config */
    private function createTransport(array $config): MailChannelsApiTransport
    {
        $transport = Mail::createSymfonyTransport(['transport' => 'mailchannels'] + $config);

        $this->assertInstanceOf(MailChannelsApiTransport::class, $transport);

        return $transport;
    }

    private function getTransportLogger(MailChannelsApiTransport $transport): mixed
    {
        return (new ReflectionMethod($transport, 'getLogger'))->invoke($transport);
    }

    public function testRegistersMailchannelsMailerDriver(): void
    {
        $this->bootProvider();

        $transport = $this->createTransport(['api_key' => 'test-key']);

        $this->assertSame('mailchannels+api://default', (string) $transport);
    }

    public function testExplicitApiKeyTakesPrecedenceOverServicesConfig(): void
    {
        $this->app->instance('config', new MockLaravelConfigRepository([
            'services.mailchannels.api_key' => '',
        ]));
        $this->bootProvider();

        // No exception means the explicit key was used
        $this->createTransport(['api_key' => 'explicit-key']);
        $this->addToAssertionCount(1);
    }

    public function testFallsBackToServicesConfigWhenMailerConfigOmitsApiKey(): void
    {
        $this->app->instance('config', new MockLaravelConfigRepository([
            'services.mailchannels.api_key' => 'fallback-key',
        ]));
        $this->bootProvider();

        $this->createTransport([]);
        $this->addToAssertionCount(1);
    }

    public function testFallsBackToServicesConfigForBaseUrl(): void
    {
        $this->app->instance('config', new MockLaravelConfigRepository([
            'services.mailchannels.base_url' => 'https://fallback.example.com',
        ]));
        $this->bootProvider();

        $transport = $this->createTransport(['api_key' => 'test-key']);

        $this->assertSame('mailchannels+api://https://fallback.example.com', (string) $transport);
    }

    public function testThrowsWhenApiKeyIsMissingEverywhere(): void
    {
        $this->bootProvider();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MAILCHANNELS_API_KEY is required for the mailchannels mail transport.');

        Mail::createSymfonyTransport(['transport' => 'mailchannels']);
    }

    public function testThrowsWhenApiKeyIsBlank(): void
    {
        $this->bootProvider();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MAILCHANNELS_API_KEY is required for the mailchannels mail transport.');

        Mail::createSymfonyTransport(['transport' => 'mailchannels', 'api_key' => "  \t \n "]);
    }

    public function testThrowsWhenApiKeyIsNotAString(): void
    {
        $this->bootProvider();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MAILCHANNELS_API_KEY is required for the mailchannels mail transport.');

        Mail::createSymfonyTransport(['transport' => 'mailchannels', 'api_key' => 12345]);
    }

    public function testPassesBaseUrlFromMailerConfigThroughToTransport(): void
    {
        $this->bootProvider();

        $transport = $this->createTransport([
            'api_key' => 'test-key',
            'base_url' => 'https://sandbox.example.com',
        ]);

        $this->assertSame('mailchannels+api://https://sandbox.example.com', (string) $transport);
    }

    public function testNonStringBaseUrlThrowsError(): void
    {
        $this->bootProvider();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base Url expects type string, integer given.');

        $this->createTransport([
            'api_key' => 'test-key',
            'base_url' => 12345,
        ]);
    }

    public function testUsesBoundPsrLoggerWhenAvailable(): void
    {
        $logger = new RecordingLogger();
        $this->app->instance(LoggerInterface::class, $logger);
        $this->bootProvider();

        $transport = $this->createTransport(['api_key' => 'test-key']);

        $this->assertSame($logger, $this->getTransportLogger($transport));
    }

    public function testDefaultsToNullLoggerWhenNoneBound(): void
    {
        $this->bootProvider();

        $transport = $this->createTransport(['api_key' => 'test-key']);

        $this->assertInstanceOf(NullLogger::class, $this->getTransportLogger($transport));
    }
}
