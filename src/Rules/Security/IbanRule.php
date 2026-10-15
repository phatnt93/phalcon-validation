<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Security;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates International Bank Account Number (IBAN).
 */
class IbanRule extends AbstractRule
{
    protected string $name = 'iban';
    protected string $defaultMessage = 'The {field} field must be a valid IBAN.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!$this->isStringable($value)) {
            return false;
        }

        $iban = strtoupper(str_replace(' ', '', (string) $value));
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/', $iban)) {
            return false;
        }

        // Move first 4 characters to end
        $reordered = substr($iban, 4) . substr($iban, 0, 4);

        // Convert letters to numbers: A = 10, B = 11, ..., Z = 35
        $numericString = '';
        foreach (str_split($reordered) as $char) {
            if (ctype_alpha($char)) {
                $numericString .= (ord($char) - 55);
            } else {
                $numericString .= $char;
            }
        }

        // Perform modulo 97 on large numeric string
        $remainder = 0;
        foreach (str_split($numericString, 7) as $chunk) {
            $remainder = (int) ($remainder . $chunk) % 97;
        }

        return $remainder === 1;
    }
}
