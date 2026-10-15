<?php

declare(strict_types=1);

namespace PhalconValidation\Adapters\Phalcon;

use Phalcon\Filter\Validation as NativePhalconValidation;
use Phalcon\Filter\Validation\Validator\Alnum;
use Phalcon\Filter\Validation\Validator\Alpha;
use Phalcon\Filter\Validation\Validator\Between;
use Phalcon\Filter\Validation\Validator\Callback;
use Phalcon\Filter\Validation\Validator\Confirmation;
use Phalcon\Filter\Validation\Validator\Date;
use Phalcon\Filter\Validation\Validator\Digit;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\ExclusionIn;
use Phalcon\Filter\Validation\Validator\File;
use Phalcon\Filter\Validation\Validator\InclusionIn;
use Phalcon\Filter\Validation\Validator\Ip;
use Phalcon\Filter\Validation\Validator\Numericality;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\Regex;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Url;
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
            $fieldStr = (string)$field;
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
        $directNative = match ($name) {
            'required' => new PresenceOf(),
            'valid_email' => new Email(),
            'valid_url' => new Url(),
            'valid_ip', 'valid_ipv4', 'valid_ipv6' => new Ip([
                'version' => match ($name) {
                    'valid_ipv4' => 4,
                    'valid_ipv6' => 6,
                    default => null,
                },
            ]),
            'min_len' => new StringLength([
                'min' => (int)($params[0] ?? 0),
            ]),
            'max_len' => new StringLength([
                'max' => (int)($params[0] ?? PHP_INT_MAX),
            ]),
            'exact_len' => new StringLength([
                'min' => (int)($params[0] ?? 0),
                'max' => (int)($params[0] ?? 0),
            ]),
            'between_len' => new StringLength([
                'min' => (int)($params[0] ?? 0),
                'max' => (int)($params[1] ?? PHP_INT_MAX),
            ]),
            'alpha' => new Alpha(),
            'alpha_numeric' => new Alnum(),
            'numeric' => new Numericality(),
            'integer' => new Digit(),
            'between_numeric' => new Between([
                'minimum' => (float)($params[0] ?? 0),
                'maximum' => (float)($params[1] ?? PHP_FLOAT_MAX),
            ]),
            'equalsfield' => new Confirmation([
                'with' => $params[0] ?? '',
            ]),
            'contains' => new InclusionIn([
                'domain' => $params,
            ]),
            'doesnt_contain_list' => new ExclusionIn([
                'domain' => $params,
            ]),
            'date' => new Date([
                'format' => $params[0] ?? 'Y-m-d',
            ]),
            'regex' => new Regex([
                'pattern' => $params[0] ?? '',
            ]),
            'required_file' => new File(),
            default => null,
        };

        if ($directNative !== null) {
            return $directNative;
        }

        // 2. Custom callable validator -> Wrap in native Phalcon Callback Validator
        if ($custom !== null) {
            return new Callback([
                'callback' => function (array $data) use ($field, $custom, $params) {
                    $value = Arr::get($data, $field);
                    return (bool)call_user_func($custom, $field, $value, $params, $data);
                },
                'message' => Validator::getGlobalTranslator()->trans('callback', [
                    '{field}' => Validator::getGlobalTranslator()->getFieldName($field),
                ], $field),
            ]);
        }

        // 3. Registered rule in RuleRegistry -> Wrap in native Phalcon Callback Validator
        $ruleInstance = Validator::getGlobalRuleRegistry()->get($name);
        if ($ruleInstance !== null) {
            $translator = Validator::getGlobalTranslator();
            $replace = [
                '{field}' => $translator->getFieldName($field),
                '{param}' => implode(', ', $params),
                '{default}' => $ruleInstance->getDefaultMessage(),
            ];
            foreach ($params as $idx => $paramVal) {
                $replace['{param:' . $idx . '}'] = $paramVal;
            }
            $errorMessage = $translator->trans($name, $replace, $field);

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
}
