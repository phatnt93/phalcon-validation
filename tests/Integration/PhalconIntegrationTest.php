<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Integration;

use Phalcon\Di\FactoryDefault;
use Phalcon\Filter\Validation as NativePhalconValidation;
use PhalconValidation\Adapters\Phalcon\PhalconFilterAdapter;
use PhalconValidation\Adapters\Phalcon\PhalconValidationAdapter;
use PhalconValidation\Adapters\Phalcon\ValidationServiceProvider;
use PhalconValidation\Exceptions\RuleNotFoundException;
use PhalconValidation\Rules\Phalcon\ExistsModelRule;
use PhalconValidation\Rules\Phalcon\UniqueModelRule;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;

class PhalconIntegrationTest extends TestCase
{
    public function testValidationServiceProviderRegistersInPhalconDi(): void
    {
        $di = new FactoryDefault();
        $provider = new ValidationServiceProvider();
        $provider->register($di);

        $this->assertTrue($di->has('validator'));
        $validator = $di->getShared('validator');
        $this->assertInstanceOf(Validator::class, $validator);

        // Verify validator works from DI
        $clean = $validator->run(['name' => 'John'], ['name' => 'required']);
        $this->assertIsArray($clean);
        $this->assertSame('John', $clean['name']);
    }

    public function testPhalconValidationAdapterBuild(): void
    {
        $this->assertTrue(PhalconValidationAdapter::isAvailable());

        $rules = [
            'email' => 'required|valid_email|min_len,5|max_len,100',
            'username' => 'required|alpha_numeric',
            'status' => 'contains,active;inactive',
        ];

        $nativeValidation = PhalconValidationAdapter::build($rules);
        $this->assertInstanceOf(NativePhalconValidation::class, $nativeValidation);

        // Native validation validate valid data
        $messages = $nativeValidation->validate([
            'email' => 'admin@example.com',
            'username' => 'admin123',
            'status' => 'active',
        ]);
        $this->assertSame(0, count($messages));

        // Native validation validate invalid data
        $invalidMessages = $nativeValidation->validate([
            'email' => 'not-an-email',
            'username' => 'admin!@#',
            'status' => 'banned',
        ]);
        $this->assertGreaterThan(0, count($invalidMessages));
    }

    public function testPhalconValidationAdapterEnforcesUnmappedRulesViaCallback(): void
    {
        $this->assertTrue(PhalconValidationAdapter::isAvailable());

        // strong_password has no direct C-extension class in Phalcon and must be bridged via Callback validator
        $nativeValidation = PhalconValidationAdapter::build([
            'password' => 'required|strong_password',
        ]);

        // Case 1: Weak password must FAIL
        $messages = $nativeValidation->validate(['password' => 'x']);
        $this->assertSame(1, count($messages));
        $this->assertSame('password', $messages[0]->getField());
        $this->assertStringContainsString('special characters', $messages[0]->getMessage());

        // Case 2: Strong password must PASS
        $validMessages = $nativeValidation->validate(['password' => 'Secret@12345']);
        $this->assertSame(0, count($validMessages));
    }

    public function testPhalconValidationAdapterThrowsOnUndefinedRule(): void
    {
        $this->expectException(RuleNotFoundException::class);

        PhalconValidationAdapter::build([
            'code' => 'required|non_existent_undefined_rule_xyz',
        ]);
    }

    public function testPhalconFilterAdapter(): void
    {
        $this->assertTrue(PhalconFilterAdapter::isAvailable());
        $filter = PhalconFilterAdapter::getInstance();
        $this->assertNotNull($filter);

        $sanitized = $filter->sanitize('  hello  ', 'trim');
        $this->assertSame('hello', $sanitized);
    }

    public function testPhalconOrmModelRulesWithMock(): void
    {
        // Define a mock model class
        if (!class_exists('PhalconValidation\Tests\Integration\MockUser')) {
            eval('
                namespace PhalconValidation\Tests\Integration;
                class MockUser {
                    public static int $mockCount = 0;
                    public static function count(array $params = []): int {
                        return self::$mockCount;
                    }
                }
            ');
        }

        $uniqueRule = new UniqueModelRule();
        $existsRule = new ExistsModelRule();

        // When count is 0 => unique passes, exists fails
        MockUser::$mockCount = 0;
        $this->assertTrue($uniqueRule->validate('email', 'new@example.com', [MockUser::class, 'email'], []));
        $this->assertFalse($existsRule->validate('email', 'missing@example.com', [MockUser::class, 'email'], []));

        // When count > 0 => unique fails, exists passes
        MockUser::$mockCount = 1;
        $this->assertFalse($uniqueRule->validate('email', 'existing@example.com', [MockUser::class, 'email'], []));
        $this->assertTrue($existsRule->validate('email', 'existing@example.com', [MockUser::class, 'email'], []));
    }
}
