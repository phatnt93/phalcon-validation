<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Content;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that value is NOT contained within the blacklisted values.
 */
class DoesntContainListRule extends AbstractRule
{
    protected string $name = 'doesnt_contain_list';
    protected string $defaultMessage = 'The {field} field contains a disallowed value.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!$this->isStringable($value)) {
            return false;
        }

        return !in_array((string) $value, array_map('strval', $params), true);
    }
}
