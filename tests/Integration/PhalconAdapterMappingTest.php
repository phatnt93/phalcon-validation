<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Integration;

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
use Phalcon\Http\Request\File as RequestFile;
use PhalconValidation\Adapters\Phalcon\PhalconFilterAdapter;
use PhalconValidation\Adapters\Phalcon\PhalconValidationAdapter;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('phalcon')]
class PhalconAdapterMappingTest extends TestCase
{
    /**
     * Rule => [native class, guarded]. Guarded rules are wrapped in a Callback that rejects input types the native
     * validator would judge differently and otherwise returns (delegates to) the native validator.
     *
     * @return array<string, array{string, class-string, bool}>
     */
    public static function nativeMappingProvider(): array
    {
        return [
            'required' => ['required', PresenceOf::class, true],
            'valid_email' => ['valid_email', Email::class, false],
            'valid_url' => ['valid_url', Url::class, false],
            'valid_ip' => ['valid_ip', Ip::class, false],
            'valid_ipv4' => ['valid_ipv4', Ip::class, false],
            'valid_ipv6' => ['valid_ipv6', Ip::class, false],
            'min_len' => ['min_len,2', StringLength::class, true],
            'max_len' => ['max_len,5', StringLength::class, true],
            'exact_len' => ['exact_len,3', StringLength::class, true],
            'between_len' => ['between_len,2;5', StringLength::class, true],
            'alpha' => ['alpha', Alpha::class, true],
            'equalsfield' => ['equalsfield,other', Confirmation::class, true],
            'date with format' => ['date,Y-m-d', Date::class, false],
            'required_file' => ['required_file', File::class, false],
        ];
    }

    /**
     * @param class-string $nativeClass
     */
    #[DataProvider('nativeMappingProvider')]
    public function testRuleMapsToNativeValidator(string $rule, string $nativeClass, bool $guarded): void
    {
        $validators = PhalconValidationAdapter::build(['field' => $rule])->getValidators();
        $this->assertCount(1, $validators['field']);
        $validator = $validators['field'][0];

        if ($guarded) {
            $this->assertInstanceOf(Callback::class, $validator);
            $callback = $validator->getOption('callback');
            $this->assertIsCallable($callback);
            $this->assertInstanceOf($nativeClass, $callback(['field' => 'abc', 'other' => 'abc']));
            $this->assertFalse($callback(['field' => $rule === 'required' ? [] : ['abc'], 'other' => 'abc']));
        } else {
            $this->assertInstanceOf($nativeClass, $validator);
        }
    }

    /**
     * Rules whose native validator disagrees with the PHP rule must use the Callback bridge.
     *
     * @return array<string, array{string}>
     */
    public static function bridgedRuleProvider(): array
    {
        return [
            'alpha_numeric' => ['alpha_numeric'],
            'numeric' => ['numeric'],
            'integer' => ['integer'],
            'between_numeric' => ['between_numeric,1;10'],
            'contains' => ['contains,a;b'],
            'doesnt_contain_list' => ['doesnt_contain_list,a;b'],
            'date without format' => ['date'],
            'regex' => ['regex,/^[a-z]+$/'],
            'registry rule' => ['starts,ab'],
        ];
    }

    #[DataProvider('bridgedRuleProvider')]
    public function testDivergentRulesUseCallbackBridge(string $rule): void
    {
        $validators = PhalconValidationAdapter::build(['field' => $rule])->getValidators();

        $this->assertInstanceOf(Callback::class, $validators['field'][0]);
        $this->assertIsBool($validators['field'][0]->getOption('callback')(['field' => 'abc']));
    }

    /**
     * Every rule the adapter maps natively (or used to) must give the same verdict as Validator::is_valid() for
     * empty, Unicode, signed, float, date-like, boolean and non-scalar input.
     *
     * @return array<string, array{string, mixed}>
     */
    public static function parityProvider(): array
    {
        $rules = [
            'required', 'valid_email', 'valid_url', 'valid_ip', 'valid_ipv4', 'valid_ipv6', 'min_len,3', 'max_len,3',
            'exact_len,3', 'between_len,2;4', 'alpha', 'alpha_numeric', 'numeric', 'integer', 'between_numeric,1;10',
            'equalsfield,other', 'contains,a;b', 'doesnt_contain_list,a;b', 'date', 'date,Y-m-d', 'date,d/m/Y',
            'regex,/^[a-z]+$/', 'regex,/[0-9]/',
        ];
        $values = [
            'empty string' => '', 'null' => null, 'empty array' => [], 'string zero' => '0', 'int zero' => 0,
            'false' => false, 'true' => true, 'abc' => 'abc', 'abc newline' => "abc\n", 'unicode' => 'Đức',
            'accented' => 'héllo', 'cjk' => '日本', 'spaces' => 'ab cd', 'alnum' => 'abc123', 'negative' => '-5',
            'signed' => '+5', 'padded' => ' 5', 'float' => '5.5', 'exponent' => '1e3', 'int' => 12, 'negative int' => -3,
            'float value' => 3.14, 'leading zero' => '007', 'hex' => '0x1A', 'dotted' => '8.8.8.8', 'ipv6' => '::1',
            'a' => 'a', 'c' => 'c', 'iso date' => '2024-01-15', 'invalid date' => '2024-02-30',
            'iso datetime' => '2024-01-15T10:00:00Z', 'dmy date' => '15/01/2024', 'email' => 'user@example.com',
            'url' => 'http://x.com', 'ftp url' => 'ftp://x.com', 'host only' => 'x.com', 'list' => ['abc'],
            'pair' => ['abc', 'd'],
        ];

        $cases = [];
        foreach ($rules as $rule) {
            foreach ($values as $label => $value) {
                $cases[$rule . ' / ' . $label] = [$rule, $value];
            }
        }
        return $cases;
    }

    #[DataProvider('parityProvider')]
    public function testNativeAdapterMatchesPhpEngine(string $rule, mixed $value): void
    {
        $data = ['f' => $value, 'other' => 'abc'];
        $expected = Validator::is_valid($data, ['f' => $rule]) === true;

        $messages = PhalconValidationAdapter::build(['f' => $rule])->validate($data);
        $this->assertSame($expected, count($messages) === 0);
    }

    public function testNativeMessagesUseTranslatorAliasAndLocale(): void
    {
        Validator::set_field_name('adapter_msg_email', 'Work Email');
        $translator = Validator::getGlobalTranslator();

        $messages = PhalconValidationAdapter::build([
            'adapter_msg_email' => 'valid_email',
            'adapter_msg_name' => 'min_len,5',
        ])->validate(['adapter_msg_email' => 'nope', 'adapter_msg_name' => 'ab']);
        $this->assertSame('The Work Email field must be a valid email address.', $messages[0]->getMessage());
        $this->assertStringContainsString('at least 5 characters', $messages[1]->getMessage());

        $translator->setLocale('vi');
        try {
            $messages = PhalconValidationAdapter::build(['adapter_msg_email' => 'valid_email'])
                ->validate(['adapter_msg_email' => 'nope']);
            $this->assertSame('Trường Work Email phải là một địa chỉ email hợp lệ.', $messages[0]->getMessage());
        } finally {
            $translator->setLocale('en');
        }
    }

    public function testNativeValidatorsEnforceParams(): void
    {
        $validation = PhalconValidationAdapter::build([
            'ip4' => 'valid_ipv4',
            'name' => 'between_len,2;5',
            'code' => 'exact_len,3',
            'qty' => 'between_numeric,1;10',
            'pick' => 'contains,a;b',
            'skip' => 'doesnt_contain_list,a;b',
        ]);

        $this->assertCount(0, $validation->validate([
            'ip4' => '8.8.8.8',
            'name' => 'abcd',
            'code' => 'abc',
            'qty' => 5,
            'pick' => 'a',
            'skip' => 'c',
        ]));

        $this->assertCount(6, $validation->validate([
            'ip4' => '::1',
            'name' => 'abcdefg',
            'code' => 'ab',
            'qty' => 50,
            'pick' => 'z',
            'skip' => 'a',
        ]));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function ipProvider(): array
    {
        $cases = [];
        foreach (['valid_ip', 'valid_ipv4', 'valid_ipv6'] as $rule) {
            foreach (['8.8.8.8', '10.0.0.1', '192.168.1.1', '127.0.0.1', '2001:4860:4860::8888', 'fd00::1', '::1', '999.1.1.1', 'abc'] as $ip) {
                $cases[$rule . ' ' . $ip] = [$rule, $ip];
            }
        }
        return $cases;
    }

    /**
     * The native Phalcon Ip validator must agree with the pure-PHP rules (private/reserved ranges allowed,
     * version restricted for ipv4/ipv6).
     */
    #[DataProvider('ipProvider')]
    public function testNativeIpMatchesPhpRule(string $rule, string $ip): void
    {
        $phpRule = Validator::getGlobalRuleRegistry()->get($rule);
        $this->assertNotNull($phpRule);
        $expected = $phpRule->validate('ip', $ip, [], []);

        $messages = PhalconValidationAdapter::build(['ip' => $rule])->validate(['ip' => $ip]);
        $this->assertSame($expected, count($messages) === 0);
    }

    public function testNativeBetweenNumericMatchesPhpRule(): void
    {
        $phpRule = Validator::getGlobalRuleRegistry()->get('between_numeric');
        $this->assertNotNull($phpRule);
        $native = PhalconValidationAdapter::build(['n' => 'between_numeric,18;65']);

        foreach (['17', '18', '40', '65', '65.5', -1] as $value) {
            $expected = $phpRule->validate('n', $value, ['18', '65'], []);
            $this->assertSame($expected, count($native->validate(['n' => $value])) === 0, 'Value ' . $value);
        }
    }

    public function testMimeTypeUsesRealContentOfPhalconRequestFile(): void
    {
        $tmpFile = (string) tempnam(sys_get_temp_dir(), 'pv_test_');
        file_put_contents($tmpFile, 'hello');
        $rule = Validator::getGlobalRuleRegistry()->get('mimetype');
        $this->assertNotNull($rule);

        try {
            $upload = ['name' => 'a.png', 'type' => 'image/png', 'tmp_name' => $tmpFile, 'size' => 5, 'error' => 0];
            $file = new RequestFile($upload);
            $this->assertFalse($rule->validate('f', $file, ['image/png'], []));
            $this->assertTrue($rule->validate('f', $file, ['text/plain'], []));

            // Missing temp file: rejected without warnings, client type is ignored
            $missing = new RequestFile(['tmp_name' => $tmpFile . '-missing'] + $upload);
            $this->assertFalse($rule->validate('f', $missing, ['image/png'], []));
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testInlineCallableRuleIsBridgedThroughCallback(): void
    {
        $validation = PhalconValidationAdapter::build([
            'age' => ['adult' => static fn(string $field, mixed $value): bool => (int) $value >= 18],
        ]);

        $this->assertInstanceOf(Callback::class, $validation->getValidators()['age'][0]);
        $this->assertCount(0, $validation->validate(['age' => 21]));
        $this->assertCount(1, $validation->validate(['age' => 10]));
    }

    public function testRegistryRuleSkipsEmptyValuesAndUsesIndexedParams(): void
    {
        $validation = PhalconValidationAdapter::build(['code' => 'starts,ab']);

        $this->assertCount(0, $validation->validate(['code' => '']));
        $this->assertCount(0, $validation->validate(['code' => 'abc']));

        $messages = $validation->validate(['code' => 'xyz']);
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('ab', (string) $messages[0]->getMessage());
    }

    public function testFilterAdapterMapsAllKnownNames(): void
    {
        $this->assertTrue(PhalconFilterAdapter::isAvailable());
        $this->assertSame(PhalconFilterAdapter::getInstance(), PhalconFilterAdapter::getInstance());

        $this->assertSame([
            'trim' => 'trim',
            'lower_case' => 'lower',
            'upper_case' => 'upper',
            'sanitize_email' => 'email',
            'sanitize_string' => 'string',
            'basic_tags' => 'striptags',
            'urlencode' => 'url',
            'boolean' => 'bool',
            'slug' => null,
        ], array_combine(
            ['trim', 'lower_case', 'upper_case', 'sanitize_email', 'sanitize_string', 'basic_tags', 'urlencode', 'boolean', 'slug'],
            array_map(
                [PhalconFilterAdapter::class, 'mapFilterName'],
                ['trim', 'lower_case', 'upper_case', 'sanitize_email', 'sanitize_string', 'basic_tags', 'urlencode', 'boolean', 'slug'],
            ),
        ));
    }
}
