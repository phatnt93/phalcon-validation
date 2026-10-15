<?php

declare(strict_types=1);

namespace PhalconValidation\Core;

use PhalconValidation\Contracts\FilterRegistryInterface;
use PhalconValidation\Contracts\RuleRegistryInterface;
use PhalconValidation\Contracts\TranslatorInterface;
use PhalconValidation\Messages\MessageBag;
use PhalconValidation\Support\Arr;

/**
 * Orchestrates the full validation and sanitization pipeline.
 */
class ValidationEngine
{
    private FilterEngine $filterEngine;
    private RuleEngine $ruleEngine;

    public function __construct(
        private RuleRegistryInterface $ruleRegistry,
        private FilterRegistryInterface $filterRegistry,
        private TranslatorInterface $translator,
    ) {
        $this->filterEngine = new FilterEngine($this->filterRegistry);
        $this->ruleEngine = new RuleEngine($this->ruleRegistry, $this->translator);
    }

    /**
     * Run the complete pipeline: filter -> validate -> check extra fields -> return ValidationResult.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $filters
     * @param bool $checkFields If true, flags any extra field not specified in $rules
     * @return ValidationResult
     */
    public function run(array $data, array $rules, array $filters = [], bool $checkFields = false): ValidationResult
    {
        // Step 1: Filter / Sanitize data
        $filteredData = $this->filterEngine->filter($data, $filters);

        // Step 2: Validate
        $messageBag = new MessageBag();
        $this->ruleEngine->validate($filteredData, $rules, $messageBag);

        // Step 3: Check extra fields if enabled
        if ($checkFields) {
            $this->checkExtraFields($filteredData, $rules, $messageBag);
        }

        return new ValidationResult($filteredData, $messageBag);
    }

    /**
     * Filter only.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function filter(array $data, array $filters): array
    {
        return $this->filterEngine->filter($data, $filters);
    }

    /**
     * Validate only.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param MessageBag $messageBag
     * @return bool
     */
    public function validate(array $data, array $rules, MessageBag $messageBag): bool
    {
        return $this->ruleEngine->validate($data, $rules, $messageBag);
    }

    /**
     * Check if input data contains fields not defined in the rule set.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param MessageBag $messageBag
     */
    private function checkExtraFields(array $data, array $rules, MessageBag $messageBag): void
    {
        $allowedFields = array_keys($rules);

        foreach (array_keys($data) as $field) {
            $fieldStr = (string) $field;
            $isAllowed = false;

            foreach ($allowedFields as $allowed) {
                $allowedStr = (string) $allowed;
                if ($allowedStr === $fieldStr) {
                    $isAllowed = true;
                    break;
                }

                // If allowed rule is nested (e.g. 'user.email') and top level is 'user'
                if (str_starts_with($allowedStr, $fieldStr . '.')) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                $msg = $this->translator->trans('check_fields', [
                    '{field}' => $this->translator->getFieldName($fieldStr),
                ], $fieldStr);
                $messageBag->add($fieldStr, 'check_fields', $msg, []);
            }
        }
    }

    public function getRuleRegistry(): RuleRegistryInterface
    {
        return $this->ruleRegistry;
    }

    public function getFilterRegistry(): FilterRegistryInterface
    {
        return $this->filterRegistry;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
