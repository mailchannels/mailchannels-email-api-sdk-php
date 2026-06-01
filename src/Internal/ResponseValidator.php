<?php

declare(strict_types=1);

namespace MailChannels\Internal;

use MailChannels\Exception\ResponseValidationException;

/**
 * Internal helpers used by typed response classes (`SendResponse`,
 * `QueuedSendResponse`, etc.) to extract and type-validate fields out of
 * the decoded response body. Missing fields become `null`; present fields
 * with the wrong type throw {@see ResponseValidationException}.
 *
 * @internal Implementation detail of MailChannels\Response\*; not part of
 *     the public SDK surface.
 */
final class ResponseValidator
{
    /** @param array<string, mixed> $body */
    public static function asNullableString(array $body, string $key): ?string
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be a string; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        return $value;
    }

    /** @param array<string, mixed> $body */
    public static function asNullableInt(array $body, string $key): ?int
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_int($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be an int; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $body
     * @return list<array<string, mixed>>|null
     */
    public static function asNullableListOfArrays(array $body, string $key): ?array
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_array($value) || !array_is_list($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be a list; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        $out = [];
        foreach ($value as $i => $item) {
            if (!is_array($item)) {
                throw new ResponseValidationException(
                    "Response field '{$key}'[{$i}] must be an associative array; got " . get_debug_type($item) . '.',
                    errorCode: 'InvalidResponseShape',
                );
            }
            $assoc = [];
            foreach ($item as $k => $v) {
                $assoc[(string) $k] = $v;
            }
            $out[] = $assoc;
        }
        return $out;
    }
}
