<?php

declare(strict_types=1);

namespace PhalconValidation\Core;

use PhalconValidation\Contracts\RuleRegistryInterface;
use PhalconValidation\Contracts\TranslatorInterface;
use PhalconValidation\Exceptions\RuleNotFoundException;
use PhalconValidation\Messages\MessageBag;
use PhalconValidation\Rules\CallbackRule;
use PhalconValidation\Support\Arr;

/**
 * Validates data arrays against rule sets, handling nested keys and wildcard patterns.
 */
class RuleEngine
{
    /**
     * Rules that must execute even if the value is null or empty string.
     */
    private const ALWAYS_EXECUTE_RULES = [
        'required',
        'required_file',
        'present',
    ];

    public function __construct(
        private RuleRegistryInterface $registry,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Validate data against rules, populating the MessageBag with any violations.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<mixed>|callable> $ruleSet
     * @param MessageBag $messageBag
     * @return bool True if valid, false if any errors were added
     * @throws RuleNotFoundException
     */
    public function validate(array $data, array $ruleSet, MessageBag $messageBag): bool
    {
        $hasErrors = false;

        foreach ($ruleSet as $fieldPattern => $ruleDef) {
            $fieldPatternStr = (string) $fieldPattern;
            $paths = Arr::expandWildcards($data, $fieldPatternStr);

            // If a wildcard pattern like 'items.*.id' expanded to empty because 'items' is empty or missing
            if (empty($paths) && str_contains($fieldPatternStr, '*')) {
                $baseField = explode('.*', $fieldPatternStr, 2)[0];
                $tokens = RuleParser::parseRules($ruleDef);
                $hasRequired = false;
                foreach ($tokens as $t) {
                    if ($t['name'] === 'required') {
                        $hasRequired = true;
                        break;
                    }
                }

                if ($hasRequired) {
                    $msg = $this->translator->trans('required', [
                        '{field}' => $this->translator->getFieldName($baseField),
                    ], $baseField);
                    $messageBag->add($baseField, 'required', $msg, []);
                    $hasErrors = true;
                }
                continue;
            }

            foreach ($paths as $path) {
                $value = Arr::get($data, $path);
                $tokens = RuleParser::parseRules($ruleDef);

                foreach ($tokens as $token) {
                    $ruleName = $token['name'];
                    $rule = $token['custom'] !== null
                        ? new CallbackRule($ruleName, $token['custom'])
                        : $this->registry->get($ruleName);

                    if ($rule === null) {
                        throw RuleNotFoundException::forRule($ruleName);
                    }

                    // Skip non-presence rules when value is empty
                    if ($this->isEmpty($value) && !in_array($ruleName, self::ALWAYS_EXECUTE_RULES, true)) {
                        continue;
                    }

                    $passes = $rule->validate($path, $value, $token['params'], $data);

                    if (!$passes) {
                        $hasErrors = true;
                        $replace = $this->buildReplacements($path, $value, $token['params'], $rule->getDefaultMessage());
                        $msg = $this->translator->trans($ruleName, $replace, $path);
                        $messageBag->add($path, $ruleName, $msg, $token['params']);

                        // Fail-fast on the first violation for this specific field
                        break;
                    }
                }
            }
        }

        return !$hasErrors;
    }

    /**
     * Check if a value is considered empty.
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Build replacement dictionary for error message templates.
     *
     * @param string $path
     * @param mixed $value
     * @param list<string> $params
     * @param string $defaultMessage
     * @return array<string, mixed>
     */
    private function buildReplacements(string $path, mixed $value, array $params, string $defaultMessage): array
    {
        $replace = [
            '{field}' => $this->translator->getFieldName($path),
            '{value}' => is_scalar($value) ? (string) $value : (is_array($value) ? json_encode($value) : gettype($value)),
            '{default}' => $defaultMessage,
        ];

        // {param} as comma-separated string
        $replace['{param}'] = implode(', ', $params);

        // Indexed parameters: {param:0}, {param:1}, ...
        foreach ($params as $idx => $val) {
            $replace['{param:' . $idx . '}'] = $val;
        }

        return $replace;
    }
}
