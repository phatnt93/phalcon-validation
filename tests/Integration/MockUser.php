<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Integration;

/**
 * Minimal stand-in for a Phalcon MVC model exposing a static count() method.
 */
final class MockUser
{
    public static int $mockCount = 0;

    /**
     * @param array<mixed> $params
     */
    public static function count(array $params = []): int
    {
        return self::$mockCount;
    }
}
