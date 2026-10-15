<?php

declare(strict_types=1);

namespace PhalconValidation\Adapters\Phalcon;

use Phalcon\Filter\Validation as NativePhalconValidation;
use Phalcon\Filter\Validation\Validator\Alpha;
use Phalcon\Filter\Validation\Validator\Callback;
use Phalcon\Filter\Validation\Validator\Confirmation;
use Phalcon\Filter\Validation\Validator\Date;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\File;
use Phalcon\Filter\Validation\Validator\Ip;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Url;
use Phalcon\Filter\Validation\ValidatorInterface;
use PhalconValidation\Core\RuleParser;
use PhalconValidation\Exceptions\RuleNotFoundException;
use PhalconValidation\Support\Arr;
use PhalconValidation\Validator;

/**
 * Adapter that translates GUMP-like pipe/array rules into native C-optimized Phalcon\Filter\Validation instances,
 * seamlessly bridging any unmapped or custom rules through Phalcon's native Callback validator so that no rule is ever dropped.
 */
class PhalconValidationAdapter
{
    /**
     * Values the pure-PHP engine treats as empty (non-presence rules are skipped for them). Passed as the native
     * 'allowEmpty' option: the list form is compared strictly, whereas 'allowEmpty' => true would also skip '0', 0 and false.
     */
    private const EMPTY_VALUES = ['', null, []];

    /**
     * Check if the Phalcon extension is loaded and available.
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('phalcon') && class_exists(NativePhalconValidation::class);
    }

    /**
     * Convert rules definition array into a native Phalcon Validation instance.
     *
     * @param array<string, mixed> $rules
     * @return NativePhalconValidation
     * @throws RuleNotFoundException
     */
    public static function build(array $rules): NativePhalconValidation
    {
        $validation = new NativePhalconValidation();

        foreach ($rules as $field => $ruleDef) {
            $fieldStr = (string) $field;
            $tokens = RuleParser::parseRules($ruleDef);

            foreach ($tokens as $token) {
                $nativeValidator = self::mapToNativeValidator($fieldStr, $token);
                $validation->add($fieldStr, $nativeValidator);
            }
        }

        return $validation;
    }

    /**
     * Map a rule token to a native Phalcon validator object, bridging to Callback validator when no direct C-validator exists.
     *
     * @param string $field
     * @param array{name: string, params: list<string>, custom: callable|null, raw: string} $token
     * @return mixed
     * @throws RuleNotFoundException
     */
    private static function mapToNativeValidator(string $field, array $token): mixed
    {
        $name = $token['name'];
        $params = $token['params'];
        $custom = $token['custom'];

        // 1. Direct C-Extension Validator Mapping for highest performance
        if ($custom === null) {
            $directNative = self::directNativeValidator($field, $name, $params);
            if ($directNative !== null) {
                return $directNative;
            }
        }

        // 2. Custom callable validator -> Wrap in native Phalcon Callback Validator
        if ($custom !== null) {
            return new Callback([
                'callback' => function (array $data) use ($field, $custom, $params) {
                    $value = Arr::get($data, $field);
                    return (bool) call_user_func($custom, $field, $value, $params, $data);
                },
                'message' => Validator::getGlobalTranslator()->trans('callback', [
                    '{field}' => Validator::getGlobalTranslator()->getFieldName($field),
                ], $field),
            ]);
        }

        // 3. Registered rule in RuleRegistry -> Wrap in native Phalcon Callback Validator
        $ruleInstance = Validator::getGlobalRuleRegistry()->get($name);
        if ($ruleInstance !== null) {
            $errorMessage = self::message($field, $name, $params);

            return new Callback([
                'callback' => function (array $data) use ($field, $ruleInstance, $params, $name) {
                    $value = Arr::get($data, $field);
                    // Skip non-presence rules if value is empty
                    if (($value === null || $value === '' || (is_array($value) && empty($value))) && $name !== 'required') {
                        return true;
                    }
                    return $ruleInstance->validate($field, $value, $params, $data);
                },
                'message' => $errorMessage,
            ]);
        }

        // 4. Rule not found -> Raise explicit exception
        throw RuleNotFoundException::forRule($name);
    }

    /**
     * Build the native C-validator for a rule, or null when the rule must go through the Callback bridge.
     *
     * Only rules whose native validator gives the same verdict as the pure-PHP rule are mapped. Rules with different
     * native semantics (alpha_numeric and integer are ASCII/unsigned, numeric accepts '8.8.8.8', date without a format
     * defaults to ISO 8601 in PHP, between_numeric/contains/doesnt_contain_list accept booleans) use the Callback bridge
     * so that both execution paths always agree.
     *
     * @param list<string> $params
     */
    private static function directNativeValidator(string $field, string $name, array $params): ?ValidatorInterface
    {
        $options = match ($name) {
            'required', 'required_file', 'valid_email', 'valid_url', 'alpha' => [],
            // Match the pure-PHP rules (FILTER_VALIDATE_IP without range flags): private and reserved ranges are valid
            'valid_ip', 'valid_ipv4', 'valid_ipv6' => [
                'version' => match ($name) {
                    'valid_ipv4' => Ip::VERSION_4,
                    'valid_ipv6' => Ip::VERSION_6,
                    default => Ip::VERSION_4 | Ip::VERSION_6,
                },
                'allowPrivate' => true,
                'allowReserved' => true,
            ],
            'min_len' => ['min' => (int) ($params[0] ?? 0)],
            'max_len' => ['max' => (int) ($params[0] ?? PHP_INT_MAX)],
            'exact_len' => ['min' => (int) ($params[0] ?? 0), 'max' => (int) ($params[0] ?? 0)],
            'between_len' => ['min' => (int) ($params[0] ?? 0), 'max' => (int) ($params[1] ?? PHP_INT_MAX)],
            'equalsfield' => ['with' => $params[0] ?? ''],
            'date' => isset($params[0]) ? ['format' => $params[0]] : null,
            default => null,
        };

        if ($options === null) {
            return null;
        }

        $message = self::message($field, $name, $params);
        if (in_array($name, ['min_len', 'max_len', 'exact_len', 'between_len'], true)) {
            // StringLength reports through its Min/Max sub-validators
            $options['messageMinimum'] = $message;
            $options['messageMaximum'] = $message;
        } else {
            $options['message'] = $message;
        }

        $allowEmpty = $name === 'required' || $name === 'required_file' ? [] : ['allowEmpty' => self::EMPTY_VALUES];
        $options += $allowEmpty;

        $native = match ($name) {
            'required' => new PresenceOf($options),
            'required_file' => new File($options),
            'valid_email' => new Email($options),
            'valid_url' => new Url($options),
            'valid_ip', 'valid_ipv4', 'valid_ipv6' => new Ip($options),
            'min_len', 'max_len', 'exact_len', 'between_len' => new StringLength($options),
            'alpha' => new Alpha($options),
            'equalsfield' => new Confirmation($options),
            default => new Date($options),
        };

        // Type guards for inputs the native validator judges differently from the PHP rule (it casts arrays/objects
        // to string or treats an empty array as present): reject them up front, otherwise delegate to the C-validator.
        $stringable = static fn(mixed $value): bool => is_scalar($value) || $value instanceof \Stringable;
        $guard = match ($name) {
            'required' => static fn(mixed $value): bool => $value !== [],
            'alpha' => is_string(...),
            'min_len', 'max_len', 'exact_len', 'between_len' => $stringable,
            'equalsfield' => $stringable,
            default => null,
        };

        if ($guard === null) {
            return $native;
        }

        $otherField = $name === 'equalsfield' ? ($params[0] ?? '') : null;

        return new Callback([
            'callback' => static function (array $data) use ($guard, $native, $field, $otherField): ValidatorInterface|bool {
                $valid = $guard(Arr::get($data, $field))
                    && ($otherField === null || $guard(Arr::get($data, $otherField)));

                return $valid ? $native : false;
            },
            'message' => $message,
        ] + $allowEmpty);
    }

    /**
     * Translate a rule's error message with the global translator, using the same placeholders as the PHP engine
     * (field alias, {param}, {param:n}, {default}).
     *
     * @param list<string> $params
     */
    private static function message(string $field, string $name, array $params): string
    {
        $translator = Validator::getGlobalTranslator();
        $rule = Validator::getGlobalRuleRegistry()->get($name);
        $replace = [
            '{field}' => $translator->getFieldName($field),
            '{param}' => implode(', ', $params),
            '{default}' => $rule !== null ? $rule->getDefaultMessage() : '',
        ];
        foreach ($params as $idx => $paramVal) {
            $replace['{param:' . $idx . '}'] = $paramVal;
        }

        return $translator->trans($name, $replace, $field);
    }
}
