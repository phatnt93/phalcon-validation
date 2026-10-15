<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Integration;

/**
 * Minimal stand-in for a Phalcon MVC model exposing a static count() method.
 */
final class MockUser
{
    public static int $mockCount = 0;

    /** @var array<mixed> Parameters passed to the last count() call. */
    public static array $lastParams = [];

    /**
     * @param array<mixed> $params
     */
    public static function count(array $params = []): int
    {
        self::$lastParams = $params;
        return self::$mockCount;
    }
}
