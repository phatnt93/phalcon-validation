<?php

declare(strict_types=1);

namespace PhalconValidation;

use PhalconValidation\Contracts\FilterRegistryInterface;
use PhalconValidation\Contracts\RuleRegistryInterface;
use PhalconValidation\Contracts\TranslatorInterface;
use PhalconValidation\Core\FilterRegistry;
use PhalconValidation\Core\RuleRegistry;
use PhalconValidation\Core\ValidationEngine;
use PhalconValidation\Core\ValidationResult;
use PhalconValidation\Messages\MessageBag;
use PhalconValidation\Messages\Translator;

/**
 * Main Validator class providing GUMP-compatible and modern fluent APIs.
 */
class Validator
{
    /**
     * Singleton / shared instances for global configurations.
     */
    private static ?RuleRegistryInterface $globalRuleRegistry = null;
    private static ?FilterRegistryInterface $globalFilterRegistry = null;
    private static ?TranslatorInterface $globalTranslator = null;

    private ValidationEngine $engine;
    private RuleRegistryInterface $ruleRegistry;
    private FilterRegistryInterface $filterRegistry;
    private TranslatorInterface $translator;

    /**
     * Rules defined on the instance.
     * @var array<string, mixed>
     */
    private array $validationRules = [];

    /**
     * Filters defined on the instance.
     * @var array<string, mixed>
     */
    private array $filterRules = [];

    /**
     * Last execution error messages.
     */
    private MessageBag $lastErrors;

    /**
     * Last execution sanitized data.
     * @var array<string, mixed>
     */
    private array $lastData = [];

    public function __construct(string $lang = 'en')
    {
        $this->ruleRegistry = self::getGlobalRuleRegistry();
        $this->filterRegistry = self::getGlobalFilterRegistry();
        $this->translator = new Translator($lang);
        $this->engine = new ValidationEngine($this->ruleRegistry, $this->filterRegistry, $this->translator);
        $this->lastErrors = new MessageBag();
    }

    // =========================================================================
    // GUMP COMPATIBLE INSTANCE API
    // =========================================================================

    /**
     * Get or set validation rules on the instance.
     *
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    public function validation_rules(array $rules = []): array
    {
        if (!empty($rules)) {
            $this->validationRules = $rules;
        }
        return $this->validationRules;
    }

    /**
     * Get or set filter rules on the instance.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function filter_rules(array $filters = []): array
    {
        if (!empty($filters)) {
            $this->filterRules = $filters;
        }
        return $this->filterRules;
    }

    /**
     * Set custom field error messages on the instance.
     *
     * @param array<string, mixed> $fields_error_messages
     * @return self
     */
    public function set_fields_error_messages(array $fields_error_messages): self
    {
        $this->translator->setCustomMessages($fields_error_messages);
        return $this;
    }

    /**
     * Run the complete pipeline (GUMP signature compatible + shorthand overload).
     *
     * Signature 1 (GUMP native): run(array $data, bool $check_fields = false)
     * Signature 2 (Shorthand): run(array $data, array $rules, array $filters = [], bool $check_fields = false)
     *
     * @param array<string, mixed> $data
     * @param mixed $rulesOrCheckFields
     * @param array<string, mixed> $filters
     * @param bool $check_fields
     * @return array<string, mixed>|false Returns filtered data on success, false on failure
     */
    public function run(
        array $data,
        mixed $rulesOrCheckFields = false,
        array $filters = [],
        bool $check_fields = false,
    ): array|false {
        $rules = $this->validationRules;
        $activeFilters = $this->filterRules;
        $doCheckFields = false;

        if (is_bool($rulesOrCheckFields)) {
            $doCheckFields = $rulesOrCheckFields;
        } elseif (is_array($rulesOrCheckFields)) {
            $rules = $rulesOrCheckFields;
            $activeFilters = $filters;
            $doCheckFields = $check_fields;
        }

        $result = $this->engine->run($data, $rules, $activeFilters, $doCheckFields);
        $this->lastData = $result->getData();
        $this->lastErrors = $result->getMessageBag();

        if (!$result->isValid()) {
            return false;
        }

        return $this->lastData;
    }

    /**
     * Validate data only without filtering.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $ruleset
     * @return true|array<string, string> Returns true if valid, or array of errors
     */
    public function validate(array $input, array $ruleset): bool|array
    {
        $messageBag = new MessageBag();
        $isValid = $this->engine->validate($input, $ruleset, $messageBag);
        $this->lastErrors = $messageBag;

        if ($isValid) {
            return true;
        }

        return $messageBag->getErrorsArray();
    }

    /**
     * Filter data only.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $filterset
     * @return array<string, mixed>
     */
    public function filter(array $input, array $filterset): array
    {
        return $this->engine->filter($input, $filterset);
    }

    /**
     * Return raw error entries from the last run/validate call.
     *
     * @return list<array{field: string, rule: string, message: string, params: array<mixed>}>
     */
    public function errors(): array
    {
        return $this->lastErrors->all();
    }

    /**
     * Return associative array of errors: [field => message].
     *
     * @return array<string, string>
     */
    public function get_errors_array(): array
    {
        return $this->lastErrors->getErrorsArray();
    }

    /**
     * Return formatted readable error messages.
     *
     * @param bool $convert_to_string
     * @param string $field_class
     * @param string $error_class
     * @return list<string>|string
     */
    public function get_readable_errors(
        bool $convert_to_string = false,
        string $field_class = 'gump-field',
        string $error_class = 'gump-error-message',
    ): array|string {
        return $this->lastErrors->getReadableErrors($convert_to_string, $field_class, $error_class);
    }

    // =========================================================================
    // MODERN FLUENT API (Return ValidationResult Object)
    // =========================================================================

    /**
     * Execute validation and return a rich ValidationResult object.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $filters
     * @param bool $check_fields
     * @return ValidationResult
     */
    public function validateData(
        array $data,
        array $rules,
        array $filters = [],
        bool $check_fields = false,
    ): ValidationResult {
        return $this->engine->run($data, $rules, $filters, $check_fields);
    }

    public function setLocale(string $locale): self
    {
        $this->translator->setLocale($locale);
        return $this;
    }

    public function setFieldNames(array $names): self
    {
        $this->translator->setFieldNames($names);
        return $this;
    }

    public function setFieldName(string $field, string $readableName): self
    {
        $this->translator->setFieldName($field, $readableName);
        return $this;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    // =========================================================================
    // STATIC GUMP API
    // =========================================================================

    /**
     * Check if data is valid in a single static call.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $validators
     * @param array<string, mixed> $fields_error_messages
     * @return true|list<string> Returns true on success, list of readable error messages on failure
     */
    public static function is_valid(
        array $data,
        array $validators,
        array $fields_error_messages = [],
    ): bool|array {
        $validator = new self();
        if (!empty($fields_error_messages)) {
            $validator->set_fields_error_messages($fields_error_messages);
        }

        $result = $validator->validate($data, $validators);
        if ($result === true) {
            return true;
        }

        /** @var list<string> $readable */
        $readable = $validator->get_readable_errors(false);
        return $readable;
    }

    public static function set_field_name(string $field, string $readable_name): void
    {
        self::getGlobalTranslator()->setFieldName($field, $readable_name);
    }

    public static function set_field_names(array $array): void
    {
        self::getGlobalTranslator()->setFieldNames($array);
    }

    public static function set_error_message(string $rule, string $message): void
    {
        self::getGlobalTranslator()->setCustomMessages([$rule => $message]);
    }

    public static function set_error_messages(array $array): void
    {
        self::getGlobalTranslator()->setCustomMessages($array);
    }

    public static function add_validator(string $rule, callable $callback, string $error_message = ''): void
    {
        self::getGlobalRuleRegistry()->addRule($rule, $callback, $error_message);
    }

    public static function add_filter(string $rule, callable $callback): void
    {
        self::getGlobalFilterRegistry()->addFilter($rule, $callback);
    }

    public static function has_validator(string $rule): bool
    {
        return self::getGlobalRuleRegistry()->has($rule);
    }

    public static function has_filter(string $filter): bool
    {
        return self::getGlobalFilterRegistry()->has($filter);
    }

    // =========================================================================
    // REGISTRY SINGLETONS
    // =========================================================================

    public static function getGlobalRuleRegistry(): RuleRegistryInterface
    {
        if (self::$globalRuleRegistry === null) {
            self::$globalRuleRegistry = new RuleRegistry();
        }
        return self::$globalRuleRegistry;
    }

    public static function getGlobalFilterRegistry(): FilterRegistryInterface
    {
        if (self::$globalFilterRegistry === null) {
            self::$globalFilterRegistry = new FilterRegistry();
        }
        return self::$globalFilterRegistry;
    }

    public static function getGlobalTranslator(): TranslatorInterface
    {
        if (self::$globalTranslator === null) {
            self::$globalTranslator = new Translator('en');
        }
        return self::$globalTranslator;
    }
}
