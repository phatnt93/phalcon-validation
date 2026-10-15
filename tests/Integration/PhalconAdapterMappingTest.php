<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Integration;

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
     * @return array<string, array{string, class-string}>
     */
    public static function nativeMappingProvider(): array
    {
        return [
            'required' => ['required', PresenceOf::class],
            'valid_email' => ['valid_email', Email::class],
            'valid_url' => ['valid_url', Url::class],
            'valid_ip' => ['valid_ip', Ip::class],
            'valid_ipv4' => ['valid_ipv4', Ip::class],
            'valid_ipv6' => ['valid_ipv6', Ip::class],
            'min_len' => ['min_len,2', StringLength::class],
            'max_len' => ['max_len,5', StringLength::class],
            'exact_len' => ['exact_len,3', StringLength::class],
            'between_len' => ['between_len,2;5', StringLength::class],
            'alpha' => ['alpha', Alpha::class],
            'alpha_numeric' => ['alpha_numeric', Alnum::class],
            'numeric' => ['numeric', Numericality::class],
            'integer' => ['integer', Digit::class],
            'between_numeric' => ['between_numeric,1;10', Between::class],
            'equalsfield' => ['equalsfield,other', Confirmation::class],
            'contains' => ['contains,a;b', InclusionIn::class],
            'doesnt_contain_list' => ['doesnt_contain_list,a;b', ExclusionIn::class],
            'date' => ['date,Y-m-d', Date::class],
            'regex' => ['regex,/^[a-z]+$/', Regex::class],
            'required_file' => ['required_file', File::class],
            'registry rule' => ['starts,ab', Callback::class],
        ];
    }

    /**
     * @param class-string $expectedClass
     */
    #[DataProvider('nativeMappingProvider')]
    public function testRuleMapsToNativeValidator(string $rule, string $expectedClass): void
    {
        $validation = PhalconValidationAdapter::build(['field' => $rule]);
        $validators = $validation->getValidators();

        $this->assertCount(1, $validators['field']);
        $this->assertInstanceOf($expectedClass, $validators['field'][0]);
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
