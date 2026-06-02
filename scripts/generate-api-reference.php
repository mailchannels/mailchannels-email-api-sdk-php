<?php

declare(strict_types=1);

/**
 * Generate docs/API_REFERENCE.md from the public surface of src/.
 *
 * Walks every PHP file under src/, introspects each class/interface/enum with
 * the Reflection API, parses PHPDoc summaries and @param/@return tags, and
 * emits a deterministic Markdown reference. Run via `composer api-reference`.
 *
 * Output is sorted by fully-qualified class name so consecutive runs against
 * unchanged source produce identical bytes (the CI freshness check relies on
 * that property).
 */

namespace MailChannels\Scripts\GenerateApiReference;

use ReflectionClass;
use ReflectionEnum;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use UnitEnum;

require __DIR__ . '/../vendor/autoload.php';

const ROOT_NAMESPACE = 'MailChannels';
const SRC_DIR = __DIR__ . '/../src';
const OUTPUT_PATH = __DIR__ . '/../docs/API_REFERENCE.md';

main();

function main(): void
{
    $classes = discoverClasses(SRC_DIR, ROOT_NAMESPACE);
    sort($classes);

    $reflections = [];
    foreach ($classes as $fqcn) {
        $r = new ReflectionClass($fqcn);
        if (!isInternal($r->getDocComment() ?: null)) {
            $reflections[$fqcn] = $r;
        }
    }

    $methodCount = 0;
    foreach ($reflections as $r) {
        $methodCount += countDocumentedMethods($r);
    }

    $output = renderHeader(count($reflections), $methodCount);
    $output .= renderExportTable($reflections);
    $output .= "\n";
    foreach ($reflections as $r) {
        $output .= renderClass($r);
    }

    file_put_contents(OUTPUT_PATH, $output);
    fwrite(STDERR, sprintf("Wrote %s (%d classes, %d documented methods)\n", OUTPUT_PATH, count($classes), $methodCount));
}

/**
 * Walk $srcDir and return every fully-qualified class name under $namespace.
 *
 * @return list<class-string>
 */
function discoverClasses(string $srcDir, string $namespace): array
{
    $absoluteSrc = realpath($srcDir);
    if ($absoluteSrc === false) {
        throw new \RuntimeException("Cannot resolve src directory: {$srcDir}");
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($absoluteSrc, \FilesystemIterator::SKIP_DOTS),
    );

    $classes = [];
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $rel = substr($file->getPathname(), strlen($absoluteSrc) + 1, -4);
        $fqcn = $namespace . '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $rel);
        if (!class_exists($fqcn) && !interface_exists($fqcn) && !enum_exists($fqcn) && !trait_exists($fqcn)) {
            continue;
        }
        /** @var class-string $fqcn */
        $classes[] = $fqcn;
    }
    return $classes;
}

function renderHeader(int $exportCount, int $methodCount): string
{
    $version = \MailChannels\Version::VERSION;
    return <<<MD
        # MailChannels PHP SDK API Reference

        This reference is generated from public class signatures and PHPDoc blocks in `src/` by `scripts/generate-api-reference.php`.

        For task-oriented examples, start with the [README](../README.md). This file is the formal public surface reference.

        - SDK version: `{$version}`
        - Top-level exports: `{$exportCount}`
        - Documented public methods: `{$methodCount}`


        MD;
}

/** @param array<string, ReflectionClass<object>> $reflections */
function renderExportTable(array $reflections): string
{
    $out = "## Top-Level Exports\n\n";
    $out .= "| Name | Kind | Summary |\n| --- | --- | --- |\n";
    foreach ($reflections as $fqcn => $r) {
        $kind = classKind($r);
        $doc = parseDoc($r->getDocComment() ?: null);
        $summary = $doc['summary'] !== '' ? $doc['summary'] : '';
        $out .= "| `{$fqcn}` | {$kind} | " . escapeTableCell($summary) . " |\n";
    }
    return $out;
}

/** @param ReflectionClass<object> $r */
function renderClass(ReflectionClass $r): string
{
    $fqcn = $r->getName();
    $kind = classKind($r);
    $doc = parseDoc($r->getDocComment() ?: null);

    $out = "## `{$fqcn}`\n\n";
    $out .= "_{$kind}_";

    $parent = $r->getParentClass();
    if ($parent !== false && str_starts_with($parent->getName(), ROOT_NAMESPACE . '\\')) {
        $out .= " — extends [`{$parent->getName()}`](#" . anchor($parent->getName()) . ')';
    }
    $out .= "\n\n";

    if ($doc['summary'] !== '') {
        $out .= $doc['summary'] . "\n\n";
    }
    if ($doc['description'] !== '') {
        $out .= $doc['description'] . "\n\n";
    }

    if ($r instanceof ReflectionEnum || $r->isEnum()) {
        $out .= renderEnumCases(new ReflectionEnum($fqcn));
    } else {
        $out .= renderConstants($r);
        $out .= renderProperties($r);
        $out .= renderConstructor($r);
        $out .= renderMethods($r);
    }

    return $out . "\n";
}

/** @param ReflectionEnum<UnitEnum> $r */
function renderEnumCases(ReflectionEnum $r): string
{
    $cases = $r->getCases();
    if ($cases === []) {
        return '';
    }
    $out = "### Cases\n\n";
    foreach ($cases as $case) {
        $value = '';
        if ($case instanceof \ReflectionEnumBackedCase) {
            $rawValue = $case->getBackingValue();
            $value = ' = ' . formatValue($rawValue);
        }
        $out .= "- `{$case->getName()}{$value}`\n";
    }
    return $out . "\n";
}

/** @param ReflectionClass<object> $r */
function renderConstants(ReflectionClass $r): string
{
    $constants = [];
    foreach ($r->getReflectionConstants() as $constant) {
        if (!$constant->isPublic() || $constant->getDeclaringClass()->getName() !== $r->getName()) {
            continue;
        }
        $constants[$constant->getName()] = $constant->getValue();
    }
    if ($constants === []) {
        return '';
    }
    ksort($constants);
    $out = "### Constants\n\n";
    foreach ($constants as $name => $value) {
        $out .= "- `{$name} = " . formatValue($value) . "`\n";
    }
    return $out . "\n";
}

/** @param ReflectionClass<object> $r */
function renderProperties(ReflectionClass $r): string
{
    $props = [];
    foreach ($r->getProperties(ReflectionProperty::IS_PUBLIC) as $p) {
        if ($p->getDeclaringClass()->getName() !== $r->getName()) {
            continue;
        }
        $props[] = $p;
    }
    if ($props === []) {
        return '';
    }
    usort($props, static fn (ReflectionProperty $a, ReflectionProperty $b): int => $a->getName() <=> $b->getName());
    $out = "### Public Properties\n\n";
    foreach ($props as $p) {
        $type = formatType($p->getType());
        $readonly = $p->isReadOnly() ? 'readonly ' : '';
        $out .= "- `{$readonly}{$type} \${$p->getName()}`\n";
    }
    return $out . "\n";
}

/** @param ReflectionClass<object> $r */
function renderConstructor(ReflectionClass $r): string
{
    $ctor = $r->getConstructor();
    if ($ctor === null || !$ctor->isPublic() || $ctor->getDeclaringClass()->getName() !== $r->getName()) {
        return '';
    }
    $out = "### Constructor\n\n";
    $out .= renderMethodBody($ctor, includeReturn: false);
    return $out . "\n";
}

/** @param ReflectionClass<object> $r */
function renderMethods(ReflectionClass $r): string
{
    $methods = [];
    foreach ($r->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->isConstructor() || $m->getDeclaringClass()->getName() !== $r->getName()) {
            continue;
        }
        if (isInternal($m->getDocComment() ?: null)) {
            continue;
        }
        $methods[] = $m;
    }
    if ($methods === []) {
        return '';
    }
    usort($methods, static fn (ReflectionMethod $a, ReflectionMethod $b): int => $a->getName() <=> $b->getName());

    $out = "### Methods\n\n";
    foreach ($methods as $m) {
        $out .= "#### `{$m->getName()}()`\n\n";
        $out .= renderMethodBody($m, includeReturn: true);
        $out .= "\n";
    }
    return $out;
}

function isInternal(?string $rawDoc): bool
{
    if ($rawDoc === null) {
        return false;
    }
    return str_contains($rawDoc, '@internal');
}

function renderMethodBody(ReflectionMethod $m, bool $includeReturn): string
{
    $signature = renderSignature($m);
    $out = "```php\n{$signature}\n```\n";

    $doc = parseDoc($m->getDocComment() ?: null);
    if ($doc['summary'] !== '') {
        $out .= "\n" . $doc['summary'] . "\n";
    }
    if ($doc['description'] !== '') {
        $out .= "\n" . $doc['description'] . "\n";
    }

    $paramDocs = [];
    foreach ($doc['tags'] as $tag) {
        if ($tag['name'] === 'param' && preg_match('/^(\S+)\s+\$(\w+)\s*(.*)$/', $tag['text'], $match)) {
            $paramDocs[$match[2]] = ['type' => $match[1], 'description' => trim($match[3])];
        }
    }

    if ($m->getNumberOfParameters() > 0) {
        $out .= "\n**Parameters:**\n";
        foreach ($m->getParameters() as $p) {
            $type = formatType($p->getType());
            $docInfo = $paramDocs[$p->getName()] ?? null;
            if ($docInfo !== null && $docInfo['type'] !== '') {
                $type = $docInfo['type'];
            }
            $description = $docInfo['description'] ?? '';
            $line = "- `\${$p->getName()}` (`{$type}`)";
            if ($description !== '') {
                $line .= " — {$description}";
            }
            $out .= $line . "\n";
        }
    }

    if ($includeReturn) {
        $returnType = formatType($m->getReturnType());
        $returnDesc = '';
        foreach ($doc['tags'] as $tag) {
            if ($tag['name'] === 'return') {
                if (preg_match('/^(\S+)\s*(.*)$/', $tag['text'], $match)) {
                    if ($match[1] !== '') {
                        $returnType = $match[1];
                    }
                    $returnDesc = trim($match[2]);
                }
                break;
            }
        }
        $out .= "\n**Returns:** `{$returnType}`";
        if ($returnDesc !== '') {
            $out .= " — {$returnDesc}";
        }
        $out .= "\n";
    }

    $throws = [];
    foreach ($doc['tags'] as $tag) {
        if ($tag['name'] === 'throws') {
            $throws[] = $tag['text'];
        }
    }
    if ($throws !== []) {
        $out .= "\n**Throws:**\n";
        foreach ($throws as $line) {
            $out .= "- {$line}\n";
        }
    }

    return $out;
}

function renderSignature(ReflectionMethod $m): string
{
    $visibility = $m->isPublic() ? 'public' : ($m->isProtected() ? 'protected' : 'private');
    $modifiers = ($m->isStatic() ? 'static ' : '') . ($m->isFinal() ? 'final ' : '');
    $params = array_map(
        static fn (ReflectionParameter $p): string => formatParameter($p),
        $m->getParameters(),
    );

    $name = $m->getName();
    $paramList = $params === [] ? '' : "\n    " . implode(",\n    ", $params) . "\n";
    $return = '';
    if (!$m->isConstructor()) {
        $type = $m->getReturnType();
        if ($type !== null) {
            $return = ': ' . formatType($type);
        }
    }
    return "{$visibility} {$modifiers}function {$name}({$paramList}){$return}";
}

function formatParameter(ReflectionParameter $p): string
{
    $type = $p->hasType() ? formatType($p->getType()) . ' ' : '';
    $variadic = $p->isVariadic() ? '...' : '';
    $byref = $p->isPassedByReference() ? '&' : '';
    $default = '';
    if ($p->isDefaultValueAvailable()) {
        $default = ' = ' . formatValue($p->getDefaultValue());
    }
    return "{$type}{$byref}{$variadic}\${$p->getName()}{$default}";
}

function formatType(?ReflectionType $type): string
{
    if ($type === null) {
        return 'mixed';
    }
    if ($type instanceof ReflectionUnionType) {
        return implode('|', array_map(static fn (ReflectionType $t): string => formatType($t), $type->getTypes()));
    }
    if ($type instanceof ReflectionIntersectionType) {
        return implode('&', array_map(static fn (ReflectionType $t): string => formatType($t), $type->getTypes()));
    }
    if ($type instanceof ReflectionNamedType) {
        $name = $type->getName();
        if ($type->allowsNull() && $name !== 'null' && $name !== 'mixed') {
            return '?' . $name;
        }
        return $name;
    }
    return (string) $type;
}

function formatValue(mixed $value): string
{
    if ($value === null) {
        return 'null';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if (is_string($value)) {
        return "'" . addcslashes($value, "'\\") . "'";
    }
    if ($value instanceof UnitEnum) {
        return '\\' . $value::class . '::' . $value->name;
    }
    if (is_array($value)) {
        return $value === [] ? '[]' : '[...]';
    }
    return '...';
}

/** @param ReflectionClass<object> $r */
function classKind(ReflectionClass $r): string
{
    if ($r->isEnum()) {
        return 'enum';
    }
    if ($r->isInterface()) {
        return 'interface';
    }
    if ($r->isTrait()) {
        return 'trait';
    }
    if ($r->isSubclassOf(\Throwable::class)) {
        return 'exception';
    }
    if ($r->isAbstract()) {
        return 'abstract class';
    }
    if ($r->isFinal()) {
        return 'final class';
    }
    return 'class';
}

/** @param ReflectionClass<object> $r */
function countDocumentedMethods(ReflectionClass $r): int
{
    $count = 0;
    foreach ($r->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->getDeclaringClass()->getName() !== $r->getName()) {
            continue;
        }
        if (isInternal($m->getDocComment() ?: null)) {
            continue;
        }
        $count++;
    }
    return $count;
}

/**
 * Minimal PHPDoc parser. Returns the summary (first paragraph), the optional
 * additional description (remaining paragraphs before the first @tag), and
 * any tags in declaration order.
 *
 * @return array{summary: string, description: string, tags: list<array{name: string, text: string}>}
 */
function parseDoc(?string $raw): array
{
    if ($raw === null || $raw === '') {
        return ['summary' => '', 'description' => '', 'tags' => []];
    }

    // Strip the surrounding /** ... */ so single-line doc blocks parse the
    // same as multi-line ones.
    $stripped = preg_replace('#^\s*/\*\*\s*|\s*\*/\s*$#', '', $raw) ?? $raw;

    $lines = preg_split('/\R/', $stripped) ?: [];
    $cleaned = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        $trimmed = preg_replace('/^\*\s?/', '', $trimmed) ?? $trimmed;
        $cleaned[] = $trimmed;
    }

    $bodyLines = [];
    $tagLines = [];
    $inTags = false;
    foreach ($cleaned as $line) {
        if (!$inTags && preg_match('/^@\w/', $line)) {
            $inTags = true;
        }
        if ($inTags) {
            $tagLines[] = $line;
        } else {
            $bodyLines[] = $line;
        }
    }

    $bodyText = trim(implode("\n", $bodyLines));
    $summary = '';
    $description = '';
    if ($bodyText !== '') {
        $paragraphs = preg_split('/\n\s*\n/', $bodyText, 2) ?: [$bodyText];
        $summary = trim(str_replace("\n", ' ', $paragraphs[0]));
        if (isset($paragraphs[1])) {
            $description = trim($paragraphs[1]);
        }
    }

    $tags = [];
    $current = null;
    foreach ($tagLines as $line) {
        if (preg_match('/^@(\w+)\s*(.*)$/', $line, $match)) {
            if ($current !== null) {
                $tags[] = $current;
            }
            $current = ['name' => $match[1], 'text' => trim($match[2])];
        } elseif ($current !== null && trim($line) !== '') {
            $current['text'] .= ' ' . trim($line);
        }
    }
    if ($current !== null) {
        $tags[] = $current;
    }

    return ['summary' => $summary, 'description' => $description, 'tags' => $tags];
}

function escapeTableCell(string $text): string
{
    $text = str_replace(["\n", '|'], [' ', '\\|'], $text);
    return trim($text);
}

function anchor(string $fqcn): string
{
    return strtolower(str_replace('\\', '', $fqcn));
}
