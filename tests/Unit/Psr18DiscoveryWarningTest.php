<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit;

use MailChannels\Client;
use MailChannels\Tests\Support\MockHttpClient;
use MailChannels\Tests\Support\RecordingLogger;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * The SDK uses php-http/discovery to pick a PSR-18 client when the caller
 * doesn't inject one. When more than one well-known client is loaded, the
 * choice is deterministic but can surprise callers. Transport emits a single
 * warning in that case so the ambiguity shows up in the application's logs.
 *
 * These tests rely on the fact that the dev environment installs both
 * `guzzlehttp/guzzle` and `symfony/http-client` (see composer.json require-dev),
 * so the multi-client condition is naturally satisfied.
 */
final class Psr18DiscoveryWarningTest extends TestCase
{
    private const WARNING_NEEDLE = 'Multiple PSR-18 HTTP clients';

    public function testWarnsWhenMultiplePsr18ClientsAreAvailableAndDiscoveryIsUsed(): void
    {
        $logger = new RecordingLogger();
        new Client(apiKey: 'mc-test', logger: $logger);

        $this->assertTrue(
            $logger->hasMessageContaining('warning', self::WARNING_NEEDLE),
            'Expected a warning when discovery has more than one PSR-18 client to choose from.',
        );
    }

    public function testDoesNotWarnWhenHttpClientIsInjectedExplicitly(): void
    {
        $logger = new RecordingLogger();
        $factory = new Psr17Factory();
        new Client(
            apiKey: 'mc-test',
            httpClient: new MockHttpClient(),
            requestFactory: $factory,
            streamFactory: $factory,
            logger: $logger,
        );

        $this->assertFalse(
            $logger->hasMessageContaining('warning', self::WARNING_NEEDLE),
            'Did not expect a warning when the caller injected a PSR-18 client.',
        );
    }
}
