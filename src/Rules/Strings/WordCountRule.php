<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Strings;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates word count of a string. Supports 'word_count,min,10,max,500' or 'word_count,10,500'.
 */
class WordCountRule extends AbstractRule
{
    protected string $name = 'word_count';
    protected string $defaultMessage = 'The {field} field must have a valid word count.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $words = preg_split('/\s+/u', trim((string)$value), -1, PREG_SPLIT_NO_EMPTY);
        $count = is_array($words) ? count($words) : 0;

        $min = 0;
        $max = PHP_INT_MAX;

        // Format 1: word_count,min,10,max,500
        if (in_array('min', $params, true) || in_array('max', $params, true)) {
            $total = count($params);
            for ($i = 0; $i < $total; $i++) {
                if ($params[$i] === 'min' && isset($params[$i + 1])) {
                    $min = (int)$params[$i + 1];
                } elseif ($params[$i] === 'max' && isset($params[$i + 1])) {
                    $max = (int)$params[$i + 1];
                }
            }
        } else {
            // Format 2: word_count,10,500
            if (isset($params[0])) {
                $min = (int)$params[0];
            }
            if (isset($params[1])) {
                $max = (int)$params[1];
            }
        }

        return $count >= $min && $count <= $max;
    }
}
