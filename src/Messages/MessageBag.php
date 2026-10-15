<?php

declare(strict_types=1);

namespace PhalconValidation\Messages;

use Countable;

/**
 * Collects and formats validation error messages.
 */
class MessageBag implements Countable
{
    /**
     * @var list<array{field: string, rule: string, message: string, params: array<mixed>}>
     */
    private array $errors = [];

    /**
     * Add an error to the bag.
     *
     * @param string $field
     * @param string $rule
     * @param string $message
     * @param array<mixed> $params
     * @return self
     */
    public function add(string $field, string $rule, string $message, array $params = []): self
    {
        $this->errors[] = [
            'field' => $field,
            'rule' => $rule,
            'message' => $message,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * Check if there are any errors, or errors for a specific field.
     */
    public function has(?string $field = null): bool
    {
        if ($field === null) {
            return !empty($this->errors);
        }

        foreach ($this->errors as $err) {
            if ($err['field'] === $field) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all raw error items.
     *
     * @return list<array{field: string, rule: string, message: string, params: array<mixed>}>
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Get associative array of errors keyed by field: ['field_name' => 'First error message'].
     *
     * @return array<string, string>
     */
    public function getErrorsArray(): array
    {
        $result = [];
        foreach ($this->errors as $err) {
            if (!isset($result[$err['field']])) {
                $result[$err['field']] = $err['message'];
            }
        }
        return $result;
    }

    /**
     * Get readable errors as an array of message strings, or formatted HTML list.
     *
     * @param bool $asHtml
     * @param string $fieldClass
     * @param string $errorClass
     * @return list<string>|string
     */
    public function getReadableErrors(
        bool $asHtml = false,
        string $fieldClass = 'gump-field',
        string $errorClass = 'gump-error-message',
    ): array|string {
        $messages = [];
        foreach ($this->errors as $err) {
            $messages[] = $err['message'];
        }

        if (!$asHtml) {
            return $messages;
        }

        if (empty($messages)) {
            return '';
        }

        $html = '<ul class="' . htmlspecialchars($errorClass, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($messages as $msg) {
            $html .= '<li>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * Clear all errors.
     */
    public function clear(): self
    {
        $this->errors = [];
        return $this;
    }
}
