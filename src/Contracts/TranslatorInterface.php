<?php

declare(strict_types=1);

namespace PhalconValidation\Contracts;

/**
 * Interface for translating error messages and resolving field labels.
 */
interface TranslatorInterface
{
    /**
     * Set the current language locale (e.g. 'en', 'vi').
     */
    public function setLocale(string $locale): self;

    /**
     * Get the current language locale.
     */
    public function getLocale(): string;

    /**
     * Translate a rule key, replacing placeholders ({field}, {param}, {value}).
     *
     * @param string $rule The rule name or error message template
     * @param array<string, mixed> $replace Replacements map
     * @param string|null $field Specific field name for field-override lookup
     * @return string
     */
    public function trans(string $rule, array $replace = [], ?string $field = null): string;

    /**
     * Set human-friendly names for multiple fields.
     *
     * @param array<string, string> $names [field_name => Readable Label]
     */
    public function setFieldNames(array $names): self;

    /**
     * Set human-friendly name for a single field.
     */
    public function setFieldName(string $field, string $readableName): self;

    /**
     * Get the human-friendly name of a field, falling back to prettified field name.
     */
    public function getFieldName(string $field): string;

    /**
     * Set custom error messages (by rule, or by field.rule).
     *
     * @param array<string, string|array<string, string>> $messages
     */
    public function setCustomMessages(array $messages): self;
}
