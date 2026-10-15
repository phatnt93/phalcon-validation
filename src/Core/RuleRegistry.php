<?php

declare(strict_types=1);

namespace PhalconValidation\Core;

use PhalconValidation\Contracts\RuleInterface;
use PhalconValidation\Contracts\RuleRegistryInterface;
use PhalconValidation\Rules\CallbackRule;

// Presence
use PhalconValidation\Rules\Presence\RequiredRule;
use PhalconValidation\Rules\Presence\RequiredFileRule;

// Strings
use PhalconValidation\Rules\Strings\MinLenRule;
use PhalconValidation\Rules\Strings\MaxLenRule;
use PhalconValidation\Rules\Strings\ExactLenRule;
use PhalconValidation\Rules\Strings\BetweenLenRule;
use PhalconValidation\Rules\Strings\WordCountRule;

// Characters
use PhalconValidation\Rules\Characters\AlphaRule;
use PhalconValidation\Rules\Characters\AlphaNumericRule;
use PhalconValidation\Rules\Characters\AlphaDashRule;
use PhalconValidation\Rules\Characters\AlphaNumericDashRule;
use PhalconValidation\Rules\Characters\AlphaNumericSpaceRule;
use PhalconValidation\Rules\Characters\AlphaSpaceRule;
use PhalconValidation\Rules\Characters\CamelCaseRule;
use PhalconValidation\Rules\Characters\SnakeCaseRule;
use PhalconValidation\Rules\Characters\UrlSlugRule;

// Numeric
use PhalconValidation\Rules\Numeric\NumericRule;
use PhalconValidation\Rules\Numeric\IntegerRule;
use PhalconValidation\Rules\Numeric\FloatRule;
use PhalconValidation\Rules\Numeric\BooleanRule;
use PhalconValidation\Rules\Numeric\MinNumericRule;
use PhalconValidation\Rules\Numeric\MaxNumericRule;
use PhalconValidation\Rules\Numeric\EvenRule;
use PhalconValidation\Rules\Numeric\OddRule;
use PhalconValidation\Rules\Numeric\PrimeRule;
use PhalconValidation\Rules\Numeric\StartsRule;

// Comparison & Regex
use PhalconValidation\Rules\Comparison\EqualsFieldRule;
use PhalconValidation\Rules\Regex\RegexRule;

// Network
use PhalconValidation\Rules\Network\ValidEmailRule;
use PhalconValidation\Rules\Network\ValidUrlRule;
use PhalconValidation\Rules\Network\UrlExistsRule;
use PhalconValidation\Rules\Network\ValidIpRule;
use PhalconValidation\Rules\Network\ValidIpv4Rule;
use PhalconValidation\Rules\Network\ValidIpv6Rule;
use PhalconValidation\Rules\Network\MacAddressRule;
use PhalconValidation\Rules\Network\DomainNameRule;
use PhalconValidation\Rules\Network\PortNumberRule;
use PhalconValidation\Rules\Network\SocialHandleRule;
use PhalconValidation\Rules\Network\ValidNameRule;

// DateTime
use PhalconValidation\Rules\DateTime\DateRule;
use PhalconValidation\Rules\DateTime\DateRangeRule;
use PhalconValidation\Rules\DateTime\FutureDateRule;
use PhalconValidation\Rules\DateTime\PastDateRule;
use PhalconValidation\Rules\DateTime\BusinessDayRule;
use PhalconValidation\Rules\DateTime\ValidTimeRule;
use PhalconValidation\Rules\DateTime\MinAgeRule;

// Geo
use PhalconValidation\Rules\Geo\LatitudeRule;
use PhalconValidation\Rules\Geo\LongitudeRule;
use PhalconValidation\Rules\Geo\CoordinatesRule;
use PhalconValidation\Rules\Geo\PostalCodeRule;
use PhalconValidation\Rules\Geo\StreetAddressRule;

// Iso
use PhalconValidation\Rules\Iso\CountryCodeRule;
use PhalconValidation\Rules\Iso\CurrencyCodeRule;
use PhalconValidation\Rules\Iso\LanguageCodeRule;
use PhalconValidation\Rules\Iso\TimezoneRule;

// Security & Finance
use PhalconValidation\Rules\Security\ValidCcRule;
use PhalconValidation\Rules\Security\IbanRule;
use PhalconValidation\Rules\Security\UuidRule;
use PhalconValidation\Rules\Security\Guidv4Rule;
use PhalconValidation\Rules\Security\JwtTokenRule;
use PhalconValidation\Rules\Security\HashRule;
use PhalconValidation\Rules\Security\Base64Rule;
use PhalconValidation\Rules\Security\StrongPasswordRule;
use PhalconValidation\Rules\Security\NoSqlInjectionRule;
use PhalconValidation\Rules\Security\NoXssRule;
use PhalconValidation\Rules\Security\PhoneNumberRule;

// Color
use PhalconValidation\Rules\Color\HexColorRule;
use PhalconValidation\Rules\Color\RgbColorRule;

// Content
use PhalconValidation\Rules\Content\ContainsRule;
use PhalconValidation\Rules\Content\ContainsListRule;
use PhalconValidation\Rules\Content\DoesntContainListRule;
use PhalconValidation\Rules\Content\ValidJsonStringRule;
use PhalconValidation\Rules\Content\ValidArraySizeEqualRule;
use PhalconValidation\Rules\Content\ValidArraySizeGreaterRule;
use PhalconValidation\Rules\Content\ValidArraySizeLesserRule;

// Files
use PhalconValidation\Rules\Files\ExtensionRule;
use PhalconValidation\Rules\Files\MimeTypeRule;
use PhalconValidation\Rules\Files\MaxFileSizeRule;
use PhalconValidation\Rules\Files\MinFileSizeRule;

// Phalcon ORM Rules
use PhalconValidation\Rules\Phalcon\UniqueModelRule;
use PhalconValidation\Rules\Phalcon\ExistsModelRule;

/**
 * Registry holding all built-in validation rules.
 */
class RuleRegistry implements RuleRegistryInterface
{
    /**
     * @var array<string, RuleInterface>
     */
    private array $rules = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * Register all core default rules.
     */
    private function registerDefaults(): void
    {
        // 1. Presence (2)
        $this->register(new RequiredRule());
        $this->register(new RequiredFileRule());

        // 2. Strings (5)
        $this->register(new MinLenRule());
        $this->register(new MaxLenRule());
        $this->register(new ExactLenRule());
        $this->register(new BetweenLenRule());
        $this->register(new WordCountRule());

        // 3. Characters (9)
        $this->register(new AlphaRule());
        $this->register(new AlphaNumericRule());
        $this->register(new AlphaDashRule());
        $this->register(new AlphaNumericDashRule());
        $this->register(new AlphaNumericSpaceRule());
        $this->register(new AlphaSpaceRule());
        $this->register(new CamelCaseRule());
        $this->register(new SnakeCaseRule());
        $this->register(new UrlSlugRule());

        // 4. Numeric (10)
        $this->register(new NumericRule());
        $this->register(new IntegerRule());
        $this->register(new FloatRule());
        $this->register(new BooleanRule());
        $this->register(new MinNumericRule());
        $this->register(new MaxNumericRule());
        $this->register(new EvenRule());
        $this->register(new OddRule());
        $this->register(new PrimeRule());
        $this->register(new StartsRule());

        // 5. Comparison & Regex (2)
        $this->register(new EqualsFieldRule());
        $this->register(new RegexRule());

        // 6. Network (11)
        $this->register(new ValidEmailRule());
        $this->register(new ValidUrlRule());
        $this->register(new UrlExistsRule());
        $this->register(new ValidIpRule());
        $this->register(new ValidIpv4Rule());
        $this->register(new ValidIpv6Rule());
        $this->register(new MacAddressRule());
        $this->register(new DomainNameRule());
        $this->register(new PortNumberRule());
        $this->register(new SocialHandleRule());
        $this->register(new ValidNameRule());

        // 7. DateTime (7)
        $this->register(new DateRule());
        $this->register(new DateRangeRule());
        $this->register(new FutureDateRule());
        $this->register(new PastDateRule());
        $this->register(new BusinessDayRule());
        $this->register(new ValidTimeRule());
        $this->register(new MinAgeRule());

        // 8. Geo (5)
        $this->register(new LatitudeRule());
        $this->register(new LongitudeRule());
        $this->register(new CoordinatesRule());
        $this->register(new PostalCodeRule());
        $this->register(new StreetAddressRule());

        // 9. Iso (4)
        $this->register(new CountryCodeRule());
        $this->register(new CurrencyCodeRule());
        $this->register(new LanguageCodeRule());
        $this->register(new TimezoneRule());

        // 10. Security & Finance (11)
        $this->register(new ValidCcRule());
        $this->register(new IbanRule());
        $this->register(new UuidRule());
        $this->register(new Guidv4Rule());
        $this->register(new JwtTokenRule());
        $this->register(new HashRule());
        $this->register(new Base64Rule());
        $this->register(new StrongPasswordRule());
        $this->register(new NoSqlInjectionRule());
        $this->register(new NoXssRule());
        $this->register(new PhoneNumberRule());

        // 11. Color (2)
        $this->register(new HexColorRule());
        $this->register(new RgbColorRule());

        // 12. Content & Array (7)
        $this->register(new ContainsRule());
        $this->register(new ContainsListRule());
        $this->register(new DoesntContainListRule());
        $this->register(new ValidJsonStringRule());
        $this->register(new ValidArraySizeEqualRule());
        $this->register(new ValidArraySizeGreaterRule());
        $this->register(new ValidArraySizeLesserRule());

        // 13. Files (1)
        $this->register(new ExtensionRule());
        $this->register(new MimeTypeRule('mimetype'));
        $this->register(new MimeTypeRule('mime_type'));
        $this->register(new MaxFileSizeRule());
        $this->register(new MinFileSizeRule());

        // 14. Phalcon Database ORM Rules (2)
        $this->register(new UniqueModelRule());
        $this->register(new ExistsModelRule());
    }

    public function register(RuleInterface $rule): self
    {
        $this->rules[$rule->getName()] = $rule;
        return $this;
    }

    public function addRule(string $name, callable $validator, string $defaultMessage = ''): self
    {
        $this->rules[$name] = new CallbackRule($name, $validator, $defaultMessage);
        return $this;
    }

    public function get(string $name): ?RuleInterface
    {
        return $this->rules[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    public function all(): array
    {
        return $this->rules;
    }
}
