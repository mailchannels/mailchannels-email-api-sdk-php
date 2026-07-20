<?php

declare(strict_types=1);

namespace MailChannels\Tests\Support;

/**
 * Minimal stand-in for Illuminate\Config\Repository. Backs the `config`
 * container binding in tests so the Config facade has something to resolve
 * against without pulling in illuminate/config.
 */
final class MockLaravelConfigRepository
{
    /** @param array<string, mixed> $items */
    public function __construct(private array $items = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }
}
