<?php

declare(strict_types=1);

namespace PhalconValidation\Rules;

use PhalconValidation\Contracts\RuleInterface;

/**
 * Base abstract class for validation rules.
 */
abstract class AbstractRule implements RuleInterface
{
    /**
     * Rule name identifier.
     */
    protected string $name;

    /**
     * Default error message template.
     */
    protected string $defaultMessage = 'The {field} field is invalid.';

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultMessage(): string
    {
        return $this->defaultMessage;
    }

    /**
     * Helper to check if a value is consider empty (null, empty string, or empty array).
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Helper to check if a value can be safely cast to string (null, scalar or Stringable).
     */
    protected function isStringable(mixed $value): bool
    {
        return $value === null || is_scalar($value) || $value instanceof \Stringable;
    }
}
