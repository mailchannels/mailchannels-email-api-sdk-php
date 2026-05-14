<?php

declare(strict_types=1);

namespace MailChannels\Tests\Support;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Minimal PSR-3 logger that records every log call. Used by tests that need
 * to assert on log output without taking a dependency on a third-party
 * logger.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /** @param array<string, mixed> $context */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => is_string($level) ? $level : (string) (is_scalar($level) ? $level : 'unknown'),
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    public function hasMessageContaining(string $level, string $needle): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level && str_contains($record['message'], $needle)) {
                return true;
            }
        }
        return false;
    }
}
