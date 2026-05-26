<?php
declare(strict_types=1);

/**
 * Enforce a minimum coverage percentage against a PHPUnit Clover report.
 *
 * Usage: php scripts/check-coverage.php <clover-file> <threshold>
 *
 * Parses the top-level <project><metrics ... elements="N" coveredelements="M">
 * element with a regex so the script has no dependency on ext-dom or
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

// Match the first <metrics ...> inside <project>. PHPUnit's Clover output
// always places aggregate totals on this element.
if (
    !preg_match(
        '/<project\b[^>]*>\s*<metrics\b([^>]*)\/?>/',
        $contents,
        $projectMetricsMatch,
    )
) {
    fwrite(STDERR, "No <project><metrics> element in {$cloverPath}\n");
    exit(2);
}

$attributes = $projectMetricsMatch[1];

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
