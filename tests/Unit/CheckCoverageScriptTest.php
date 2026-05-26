<?php

declare(strict_types=1);

namespace MailChannels\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for scripts/check-coverage.php. The script is invoked
 * from CI to enforce a minimum coverage percentage against PHPUnit's Clover
 * report; an earlier version regressed because its regex expected the
 * aggregate <metrics> immediately after <project>, which Clover never emits.
 *
 * The fixture mirrors a real PHPUnit Clover layout (per-file <metrics> inside
 * <file>, aggregate <metrics files="N"> at the end of <project>) with a
 * project-level coverage of 80 / 100 = 80.00%.
 */
final class CheckCoverageScriptTest extends TestCase
{
    private const SCRIPT = __DIR__ . '/../../scripts/check-coverage.php';
    private const FIXTURE = __DIR__ . '/../Support/clover-fixture.xml';

    public function testExitsZeroWhenCoverageMeetsThreshold(): void
    {
        [$exit, $output] = $this->runScript(self::FIXTURE, '75');

        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('80.00%', $output);
        $this->assertStringContainsString('80 / 100 elements', $output);
    }

    public function testExitsZeroWhenCoverageExactlyAtThreshold(): void
    {
        [$exit] = $this->runScript(self::FIXTURE, '80');

        $this->assertSame(0, $exit);
    }

    public function testExitsOneWhenCoverageBelowThreshold(): void
    {
        [$exit, $output] = $this->runScript(self::FIXTURE, '90');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Coverage below threshold', $output);
    }

    public function testExitsTwoWhenFileMissing(): void
    {
        [$exit, $output] = $this->runScript('/tmp/does-not-exist-' . uniqid() . '.xml', '85');

        $this->assertSame(2, $exit);
        $this->assertStringContainsString('Coverage file not found', $output);
    }

    public function testExitsTwoWhenAggregateMetricsMissing(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'clover-no-aggregate-');
        $this->assertNotFalse($tmp);
        file_put_contents(
            $tmp,
            "<?xml version=\"1.0\"?>\n<coverage><project><file name=\"x\">"
            . '<metrics elements="10" coveredelements="5"/></file></project></coverage>',
        );

        try {
            [$exit, $output] = $this->runScript($tmp, '85');
            $this->assertSame(2, $exit);
            $this->assertStringContainsString('No project-level <metrics', $output);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * @return array{int, string}
     */
    private function runScript(string $cloverPath, string $threshold): array
    {
        $command = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(self::SCRIPT),
            escapeshellarg($cloverPath),
            escapeshellarg($threshold),
        );

        exec($command, $lines, $exit);

        return [$exit, implode("\n", $lines)];
    }
}
