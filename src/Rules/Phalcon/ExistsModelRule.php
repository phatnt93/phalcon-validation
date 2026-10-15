<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Phalcon;

use PhalconValidation\Exceptions\InvalidRuleConfigurationException;
use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that field value exists in database via Phalcon MVC Model.
 * Syntax: exists,ModelClass,column
 *
 * Throws InvalidRuleConfigurationException when the model class is missing or does not exist.
 */
class ExistsModelRule extends AbstractRule
{
    protected string $name = 'exists';
    protected string $defaultMessage = 'The selected {field} is invalid.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $modelClass = $params[0] ?? '';
        if ($modelClass === '' || !class_exists($modelClass)) {
            throw InvalidRuleConfigurationException::modelNotFound($this->name, $modelClass);
        }

        $column = $params[1] ?? $field;

        /** @var int $count */
        $count = $modelClass::count([
            'conditions' => "{$column} = :val:",
            'bind' => ['val' => $value],
        ]);

        return $count > 0;
    }
}
