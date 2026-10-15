<?php

declare(strict_types=1);

namespace PhalconValidation\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a rule is configured incorrectly by the developer (e.g. it references a model class
 * that does not exist). This is a programming error, not a validation failure of the input data.
 */
class InvalidRuleConfigurationException extends InvalidArgumentException
{
    public static function modelNotFound(string $ruleName, string $modelClass): self
    {
        if ($modelClass === '') {
            return new self("Validation rule '{$ruleName}' requires a model class as its first parameter.");
        }

        return new self("Validation rule '{$ruleName}' references model class '{$modelClass}', which does not exist.");
    }
}
