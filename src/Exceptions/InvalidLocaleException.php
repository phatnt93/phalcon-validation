<?php

declare(strict_types=1);

namespace PhalconValidation\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a locale code is malformed (e.g. contains path characters).
 */
class InvalidLocaleException extends InvalidArgumentException
{
    public static function forLocale(string $locale): self
    {
        return new self("Invalid locale code '{$locale}'. Expected format like 'en', 'vi' or 'en_US'.");
    }
}
