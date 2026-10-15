<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Phalcon;

use PhalconValidation\Rules\AbstractRule;
use PhalconValidation\Support\Arr;

/**
 * Validates that field value is unique in database via Phalcon MVC Model.
 * Syntax: unique,ModelClass,column,[ignoreIdColumn],[ignoreIdValue]
 */
class UniqueModelRule extends AbstractRule
{
    protected string $name = 'unique';
    protected string $defaultMessage = 'The {field} has already been taken.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $modelClass = $params[0] ?? '';
        if ($modelClass === '' || !class_exists($modelClass)) {
            return false;
        }

        $column = $params[1] ?? $field;
        $ignoreCol = $params[2] ?? null;
        $ignoreVal = $params[3] ?? null;

        if ($ignoreCol !== null && $ignoreVal === null) {
            $ignoreVal = Arr::get($data, $ignoreCol);
        }

        $conditions = "{$column} = :val:";
        $bind = ['val' => $value];

        if ($ignoreCol !== null && $ignoreVal !== null && $ignoreVal !== '') {
            $conditions .= " AND {$ignoreCol} != :ignoreVal:";
            $bind['ignoreVal'] = $ignoreVal;
        }

        /** @var int $count */
        $count = $modelClass::count([
            'conditions' => $conditions,
            'bind' => $bind,
        ]);

        return $count === 0;
    }
}
