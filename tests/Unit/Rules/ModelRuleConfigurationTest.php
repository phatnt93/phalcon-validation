<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Exceptions\InvalidRuleConfigurationException;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A missing or misspelled model class is a developer error and must not be reported as invalid input.
 */
class ModelRuleConfigurationTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function misconfiguredRuleProvider(): array
    {
        return [
            'unique unknown class' => ['unique,App\Models\DoesNotExist,email', "references model class 'App\\Models\\DoesNotExist'"],
            'exists unknown class' => ['exists,App\Models\DoesNotExist,id', "references model class 'App\\Models\\DoesNotExist'"],
            'unique without class' => ['unique', 'requires a model class'],
            'exists without class' => ['exists', 'requires a model class'],
        ];
    }

    #[DataProvider('misconfiguredRuleProvider')]
    public function testMisconfiguredModelRuleThrows(string $rule, string $message): void
    {
        $this->expectException(InvalidRuleConfigurationException::class);
        $this->expectExceptionMessage($message);

        Validator::is_valid(['f' => 'value'], ['f' => $rule]);
    }

    public function testEmptyValueSkipsModelRuleBeforeConfigurationCheck(): void
    {
        $this->assertTrue(Validator::is_valid(['f' => ''], ['f' => 'unique,App\Models\DoesNotExist,email']));
    }
}
