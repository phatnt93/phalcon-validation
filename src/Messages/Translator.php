<?php

declare(strict_types=1);

namespace PhalconValidation\Messages;

use PhalconValidation\Contracts\TranslatorInterface;
use PhalconValidation\Exceptions\InvalidLocaleException;
use PhalconValidation\Support\Str;

/**
 * Message translator handling i18n, field aliases and placeholder replacements.
 */
class Translator implements TranslatorInterface
{
    /**
     * Default locale and fallback dictionary for missing keys.
     */
    public const DEFAULT_LOCALE = 'en';

    /**
     * Locales shipped with the library in src/I18n/.
     */
    public const SUPPORTED_LOCALES = ['en', 'vi'];

    private string $locale = self::DEFAULT_LOCALE;

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

    /**
     * @throws InvalidLocaleException When the locale code is malformed.
     */
    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->loadLocale(self::DEFAULT_LOCALE);
        $this->setLocale($locale);
    }

    /**
     * Accepts codes like 'en', 'vi', 'en_US' (case-insensitive, '-' or '_' separator).
     * A well-formed locale without a dictionary falls back to the default locale.
     *
     * @throws InvalidLocaleException When the locale code is malformed.
     */
    public function setLocale(string $locale): self
    {
        $this->locale = self::normalizeLocale($locale);
        $this->loadLocale($this->locale);
        $this->loadLocale(self::baseLanguage($this->locale));
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setFieldNames(array $names): self
    {
        foreach ($names as $field => $label) {
            $this->fieldNames[(string) $field] = (string) $label;
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
        $wildcardPattern = (string) preg_replace('/\.\d+\./', '.*.', $field);
        if (isset($this->fieldNames[$wildcardPattern])) {
            return $this->fieldNames[$wildcardPattern];
        }

        return Str::humanize($field);
    }

    public function setCustomMessages(array $messages): self
    {
        foreach ($messages as $key => $msg) {
            $this->customMessages[(string) $key] = $msg;
        }
        return $this;
    }

    /**
     * Add or override translations for a locale without modifying the library
     * (e.g. ship a 'fr' dictionary from the application).
     *
     * @param array<string, string> $messages [rule => template]
     * @throws InvalidLocaleException When the locale code is malformed.
     */
    public function addTranslations(string $locale, array $messages): self
    {
        $locale = self::normalizeLocale($locale);
        $this->loadLocale($locale);

        foreach ($messages as $rule => $template) {
            $this->dictionaries[$locale][(string) $rule] = (string) $template;
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
            $values[] = is_array($val) ? implode(', ', $val) : (string) $val;
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

        // 3b. Check base language dictionary ('vi' for 'vi_VN')
        $base = self::baseLanguage($this->locale);
        if (isset($this->dictionaries[$base][$rule])) {
            return $this->dictionaries[$base][$rule];
        }

        // 4. Check fallback default locale dictionary
        if (isset($this->dictionaries[self::DEFAULT_LOCALE][$rule])) {
            return $this->dictionaries[self::DEFAULT_LOCALE][$rule];
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

        $this->dictionaries[$locale] = [];

        // Only shipped locales are read from disk, so user input never reaches the file path.
        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            return;
        }

        $dict = require dirname(__DIR__) . '/I18n/' . $locale . '.php';
        if (is_array($dict)) {
            $this->dictionaries[$locale] = $dict;
        }
    }

    /**
     * Normalize 'EN', 'en-us', 'vi_VN' to 'en', 'en_US', 'vi_VN'.
     *
     * @throws InvalidLocaleException When the locale code is malformed.
     */
    private static function normalizeLocale(string $locale): string
    {
        if (preg_match('/^([a-zA-Z]{2,3})(?:[_-]([a-zA-Z]{2}))?$/', $locale, $matches) !== 1) {
            throw InvalidLocaleException::forLocale($locale);
        }

        $normalized = strtolower($matches[1]);
        if (isset($matches[2])) {
            $normalized .= '_' . strtoupper($matches[2]);
        }

        return $normalized;
    }

    private static function baseLanguage(string $locale): string
    {
        return explode('_', $locale, 2)[0];
    }
}
