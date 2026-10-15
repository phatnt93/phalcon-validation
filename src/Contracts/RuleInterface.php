<?php

declare(strict_types=1);

namespace PhalconValidation\Contracts;

/**
 * Interface for all validation rules.
 */
interface RuleInterface
{
    /**
     * Get the unique name of the rule (e.g. 'required', 'min_len', 'valid_email').
     */
    public function getName(): string;

    /**
     * Validate a value against this rule.
     *
     * @param string $field The field name being validated (may be dot-notated like 'user.email')
     * @param mixed $value The value of the field being validated
     * @param array<int|string, mixed> $params Parameters passed to the rule (e.g. ['6'] for min_len,6)
     * @param array<string, mixed> $data The entire input dataset (useful for cross-field validation like equalsfield)
     * @return bool True if valid, false otherwise
     */
    public function validate(string $field, mixed $value, array $params, array $data): bool;

    /**
     * Get the default error message template.
     * May contain placeholders like {field}, {param}, {value}.
     */
    public function getDefaultMessage(): string;
}
