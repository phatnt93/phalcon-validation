<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates credit card number using the Luhn algorithm.
 */
class ValidCcRule extends AbstractRule
{
    protected string $name = 'valid_cc';
    protected string $defaultMessage = 'The {field} field must be a valid credit card number.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        $number = (string) preg_replace('/\D/', '', (string) $value);
        $numDigits = strlen($number);
        if ($numDigits < 13 || $numDigits > 19) {
            return false;
        }

        $sum = 0;
        $parity = $numDigits % 2;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int) $number[$i];
            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
