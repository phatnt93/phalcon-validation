<?php

declare(strict_types=1);

namespace PhalconValidation\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a requested validation rule is not registered.
 */
class RuleNotFoundException extends InvalidArgumentException
{
    public static function forRule(string $ruleName): self
    {
        return new self("Validation rule '{$ruleName}' is not defined or registered.");
    }
}
