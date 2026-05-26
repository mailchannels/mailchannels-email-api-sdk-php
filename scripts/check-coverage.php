<?php
declare(strict_types=1);

/**
 * Enforce a minimum coverage percentage against a PHPUnit Clover report.
 *
 * Usage: php scripts/check-coverage.php <clover-file> <threshold>
 *
 * Reads the project-level aggregate <metrics> element. Clover output places
 * the aggregate at the end of <project> after all <file> children, and it is
 * the only <metrics> element with a `files="N"` attribute — per-file metrics
 * describe a single file, per-class metrics only carry method counts. Matching
 * on `files=` is position-independent and avoids depending on ext-dom or
 * ext-simplexml (which Ubuntu's php-cli package omits by default).
 *
 * Exit codes:
 *   0  coverage meets or exceeds the threshold
 *   1  coverage is below the threshold
 *   2  the report could not be read or parsed
 */

$cloverPath = $argv[1] ?? 'coverage.xml';
$threshold = isset($argv[2]) ? (float) $argv[2] : 0.0;

if (!is_file($cloverPath)) {
    fwrite(STDERR, "Coverage file not found: {$cloverPath}\n");
    exit(2);
}

$contents = file_get_contents($cloverPath);
if ($contents === false || $contents === '') {
    fwrite(STDERR, "Coverage file is empty: {$cloverPath}\n");
    exit(2);
}

if (
    !preg_match(
        '/<metrics\b[^>]*\bfiles="\d+"[^>]*\/?>/',
        $contents,
        $metricsMatch,
    )
) {
    fwrite(STDERR, "No project-level <metrics files=\"...\"> element in {$cloverPath}\n");
    exit(2);
}

$attributes = $metricsMatch[0];

if (!preg_match('/\belements="(\d+)"/', $attributes, $elementsMatch)) {
    fwrite(STDERR, "No 'elements' attribute on <metrics>\n");
    exit(2);
}
if (!preg_match('/\bcoveredelements="(\d+)"/', $attributes, $coveredMatch)) {
    fwrite(STDERR, "No 'coveredelements' attribute on <metrics>\n");
    exit(2);
}

$elements = (int) $elementsMatch[1];
$covered = (int) $coveredMatch[1];

if ($elements === 0) {
    fwrite(STDERR, "No coverable elements reported\n");
    exit(2);
}

$percent = ($covered / $elements) * 100.0;

printf(
    "Coverage: %.2f%% (%d / %d elements) — threshold %.2f%%\n",
    $percent,
    $covered,
    $elements,
    $threshold,
);

if ($percent + 0.0001 < $threshold) {
    fwrite(STDERR, "Coverage below threshold\n");
    exit(1);
}
