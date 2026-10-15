<?php

declare(strict_types=1);

namespace PhalconValidation;

use PhalconValidation\Core\ValidationResult;

/**
 * Static Facade for PhalconValidation.
 */
class PhalconValidation
{
    /**
     * Create a new Validator instance.
     */
    public static function create(string $lang = 'en'): Validator
    {
        return new Validator($lang);
    }

    /**
     * Shorthand to validate and sanitize data in one static call.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $filters
     * @param bool $check_fields
     * @param string $lang
     * @return array<string, mixed>|false
     */
    public static function run(
        array $data,
        array $rules,
        array $filters = [],
        bool $check_fields = false,
        string $lang = 'en'
    ): array|false {
        $validator = new Validator($lang);
        return $validator->run($data, $rules, $filters, $check_fields);
    }

    /**
     * Validate data and return rich ValidationResult object.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $filters
     * @param bool $check_fields
     * @param string $lang
     * @return ValidationResult
     */
    public static function check(
        array $data,
        array $rules,
        array $filters = [],
        bool $check_fields = false,
        string $lang = 'en'
    ): ValidationResult {
        $validator = new Validator($lang);
        return $validator->validateData($data, $rules, $filters, $check_fields);
    }

    /**
     * Quick static boolean check if data conforms to rules.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $customMessages
     * @param string $lang
     * @return true|list<string> True if valid, list of error messages if invalid
     */
    public static function isValid(
        array $data,
        array $rules,
        array $customMessages = [],
        string $lang = 'en'
    ): true|array {
        $validator = new Validator($lang);
        if (!empty($customMessages)) {
            $validator->set_fields_error_messages($customMessages);
        }

        $result = $validator->validate($data, $rules);
        if ($result === true) {
            return true;
        }

        /** @var list<string> $readable */
        $readable = $validator->get_readable_errors(false);
        return $readable;
    }
}
