<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Characters;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is formatted as a valid URL slug (lowercase letters, digits, and hyphens).
 */
class UrlSlugRule extends AbstractRule
{
    protected string $name = 'url_slug';
    protected string $defaultMessage = 'The {field} field must be a valid URL slug.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        return is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) === 1;
    }
}
