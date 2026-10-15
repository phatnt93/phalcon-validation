<?php

declare(strict_types=1);

namespace PhalconValidation\Support;

use ArrayAccess;

/**
 * Array helper providing dot-notation and wildcard (*) expansions.
 */
class Arr
{
    /**
     * Get an item from an array or ArrayAccess using "dot" notation.
     *
     * @param array<string|int, mixed>|ArrayAccess $array
     * @param string|int|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(array|ArrayAccess $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains((string) $key, '.')) {
            return $array[$key] ?? $default;
        }

        $current = $array;
        foreach (explode('.', (string) $key) as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif ($current instanceof ArrayAccess && $current->offsetExists($segment)) {
                $current = $current[$segment];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param array<string|int, mixed>|ArrayAccess $array
     * @param string|int $key
     * @return bool
     */
    public static function has(array|ArrayAccess $array, string|int $key): bool
    {
        if (is_array($array) && array_key_exists($key, $array)) {
            return true;
        }

        $segments = explode('.', (string) $key);
        $current = $array;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } elseif ($current instanceof ArrayAccess && $current->offsetExists($segment)) {
                $current = $current[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param array<string|int, mixed> $array
     * @param string|int|null $key
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    public static function set(array &$array, string|int|null $key, mixed $value): array
    {
        if ($key === null) {
            return $array = is_array($value) ? $value : [$value];
        }

        $keys = explode('.', (string) $key);
        $current = &$array;

        while (count($keys) > 1) {
            $k = array_shift($keys);

            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }

            $current = &$current[$k];
        }

        $current[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Expand wildcard patterns like 'items.*.name' against actual data.
     * Returns an array of resolved concrete dot-notation paths (e.g. ['items.0.name', 'items.1.name']).
     *
     * @param array<string|int, mixed> $data
     * @param string $pattern
     * @return list<string>
     */
    public static function expandWildcards(array $data, string $pattern): array
    {
        if (!str_contains($pattern, '*')) {
            return [$pattern];
        }

        $segments = explode('.', $pattern);
        return self::expandSegments($data, $segments, '');
    }

    /**
     * Recursive helper to resolve wildcard segments.
     *
     * @param mixed $currentData
     * @param list<string> $segments
     * @param string $prefix
     * @return list<string>
     */
    private static function expandSegments(mixed $currentData, array $segments, string $prefix): array
    {
        if (empty($segments)) {
            return [$prefix];
        }

        $segment = array_shift($segments);

        if ($segment === '*') {
            if (!is_array($currentData) || empty($currentData)) {
                return [];
            }

            $results = [];
            foreach (array_keys($currentData) as $key) {
                $nextPrefix = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                $childResults = self::expandSegments($currentData[$key], $segments, $nextPrefix);
                foreach ($childResults as $child) {
                    $results[] = $child;
                }
            }
            return $results;
        }

        $nextPrefix = $prefix === '' ? $segment : $prefix . '.' . $segment;
        $nextData = is_array($currentData) && array_key_exists($segment, $currentData)
            ? $currentData[$segment]
            : null;

        return self::expandSegments($nextData, $segments, $nextPrefix);
    }

    /**
     * Determine whether an array is associative.
     *
     * @param array<mixed> $arr
     * @return bool
     */
    public static function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
