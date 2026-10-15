<?php

declare(strict_types=1);

namespace PhalconValidation\Messages;

use PhalconValidation\Contracts\TranslatorInterface;
use PhalconValidation\Support\Str;

/**
 * Message translator handling i18n, field aliases and placeholder replacements.
 */
class Translator implements TranslatorInterface
{
    private string $locale = 'en';

    /**
     * Loaded translations indexed by locale.
     * @var array<string, array<string, string>>
     */
    private array $dictionaries = [];

    /**
     * Field aliases: [technical_field_name => Human Readable Label].
     * @var array<string, string>
     */
    private array $fieldNames = [];

    /**
     * Custom messages: [rule => template] or [field => [rule => template]].
     * @var array<string, string|array<string, string>>
     */
    private array $customMessages = [];

    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
        $this->loadLocale('en');
        if ($locale !== 'en') {
            $this->loadLocale($locale);
        }
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        $this->loadLocale($locale);
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setFieldNames(array $names): self
    {
        foreach ($names as $field => $label) {
            $this->fieldNames[(string)$field] = (string)$label;
        }
        return $this;
    }

    public function setFieldName(string $field, string $readableName): self
    {
        $this->fieldNames[$field] = $readableName;
        return $this;
    }

    public function getFieldName(string $field): string
    {
        if (isset($this->fieldNames[$field])) {
            return $this->fieldNames[$field];
        }

        // For nested fields like 'items.0.price', check if there's a wildcard alias 'items.*.price'
        $wildcardPattern = (string)preg_replace('/\.\d+\./', '.*.', $field);
        if (isset($this->fieldNames[$wildcardPattern])) {
            return $this->fieldNames[$wildcardPattern];
        }

        return Str::humanize($field);
    }

    public function setCustomMessages(array $messages): self
    {
        foreach ($messages as $key => $msg) {
            $this->customMessages[(string)$key] = $msg;
        }
        return $this;
    }

    public function trans(string $rule, array $replace = [], ?string $field = null): string
    {
        $template = $this->resolveTemplate($rule, $field, $replace['{default}'] ?? null);

        // Resolve {field}
        if (!isset($replace['{field}']) && $field !== null) {
            $replace['{field}'] = $this->getFieldName($field);
        }

        // Build substitution map
        $search = [];
        $values = [];

        foreach ($replace as $placeholder => $val) {
            $search[] = $placeholder;
            $values[] = is_array($val) ? implode(', ', $val) : (string)$val;
        }

        return str_replace($search, $values, $template);
    }

    /**
     * Resolve the raw template by searching custom messages, current locale and fallback locale.
     */
    private function resolveTemplate(string $rule, ?string $field, ?string $default): string
    {
        // 1. Check field-specific custom message: 'user.email' => ['required' => '...']
        if ($field !== null && isset($this->customMessages[$field])) {
            $fieldMsgs = $this->customMessages[$field];
            if (is_array($fieldMsgs) && isset($fieldMsgs[$rule])) {
                return $fieldMsgs[$rule];
            }
            if (is_string($fieldMsgs)) {
                return $fieldMsgs;
            }
        }

        // Check dot-syntax custom message: 'user.email.required' => '...'
        if ($field !== null && isset($this->customMessages[$field . '.' . $rule])) {
            $dotMsg = $this->customMessages[$field . '.' . $rule];
            if (is_string($dotMsg)) {
                return $dotMsg;
            }
        }

        // 2. Check general custom message for rule: 'required' => '...'
        if (isset($this->customMessages[$rule]) && is_string($this->customMessages[$rule])) {
            return $this->customMessages[$rule];
        }

        // 3. Check current locale dictionary
        if (isset($this->dictionaries[$this->locale][$rule])) {
            return $this->dictionaries[$this->locale][$rule];
        }

        // 4. Check fallback 'en' dictionary
        if (isset($this->dictionaries['en'][$rule])) {
            return $this->dictionaries['en'][$rule];
        }

        // 5. Check passed default
        if ($default !== null && $default !== '') {
            return $default;
        }

        return 'The {field} field is invalid.';
    }

    /**
     * Load language dictionary from file.
     */
    private function loadLocale(string $locale): void
    {
        if (isset($this->dictionaries[$locale])) {
            return;
        }

        $file = dirname(__DIR__) . '/I18n/' . $locale . '.php';
        if (file_exists($file)) {
            $dict = require $file;
            if (is_array($dict)) {
                $this->dictionaries[$locale] = $dict;
                return;
            }
        }

        $this->dictionaries[$locale] = [];
    }
}
