<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Comparison;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\Arr;

/**
 * Validates that field value equals the value of another field in the dataset.
 */
class EqualsFieldRule extends AbstractRule
{
    protected string $name = 'equalsfield';
    protected string $defaultMessage = 'The {field} field must equal the {param:0} field.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $targetField = $params[0] ?? '';
        if ($targetField === '') {
            return false;
        }

        $otherValue = Arr::get($data, $targetField);
        if (!$this->isStringable($value) || !$this->isStringable($otherValue)) {
            return false;
        }

        return (string) $value === (string) $otherValue;
    }
}
