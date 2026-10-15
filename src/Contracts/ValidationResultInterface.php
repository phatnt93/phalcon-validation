<?php

declare(strict_types=1);

namespace PhalconValidation\Contracts;

/**
 * Interface representing the result of a validation/run pipeline.
 */
interface ValidationResultInterface
{
    /**
     * Determine if validation passed.
     */
    public function isValid(): bool;

    /**
     * Get the sanitized and filtered data (returns all input or only validated fields).
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Get raw errors list.
     *
     * @return array<int, array{field: string, rule: string, message: string, params: array}>
     */
    public function getErrors(): array;

    /**
     * Get associative errors keyed by field name: ['username' => 'The username field is required'].
     *
     * @return array<string, string>
     */
    public function getErrorsArray(): array;

    /**
     * Get human-readable formatted errors (array of strings, or an HTML unordered list).
     *
     * @param bool $asHtml If true, returns an HTML <ul><li> structure
     * @param string $fieldClass CSS class for the field span
     * @param string $errorClass CSS class for the error span
     * @return array<int, string>|string
     */
    public function getReadableErrors(
        bool $asHtml = false,
        string $fieldClass = 'gump-field',
        string $errorClass = 'gump-error-message',
    ): array|string;
}
