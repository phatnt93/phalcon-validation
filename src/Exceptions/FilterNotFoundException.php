<?php

declare(strict_types=1);

namespace PhalconValidation\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a requested filter is not registered.
 */
class FilterNotFoundException extends InvalidArgumentException
{
    public static function forFilter(string $filterName): self
    {
        return new self("Filter '{$filterName}' is not defined or registered.");
    }
}
