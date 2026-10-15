<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Core;

use PhalconValidation\Core\FilterRegistry;
use PhalconValidation\Core\RuleParser;
use PhalconValidation\Core\RuleRegistry;
use PhalconValidation\Core\ValidationEngine;
use PhalconValidation\Core\ValidationResult;
use PhalconValidation\Exceptions\FilterNotFoundException;
use PhalconValidation\Messages\MessageBag;
use PhalconValidation\Messages\Translator;
use PhalconValidation\PhalconValidation;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;

class CoreEdgeCasesTest extends TestCase
{
    // --- RuleParser ---------------------------------------------------------

    public function testParseTopLevelCallable(): void
    {
        $fn = static fn(mixed $v): bool => true;
        $parsed = RuleParser::parseRules($fn);
        $this->assertSame('callback', $parsed[0]['name']);
        $this->assertSame($fn, $parsed[0]['custom']);

        $parsed = RuleParser::parseRules('strlen');
        $this->assertSame('strlen', $parsed[0]['name']);
        $this->assertNull($parsed[0]['custom']);
    }

    public function testParseArrayRuleFormats(): void
    {
        $fn = static fn(mixed $v): bool => true;
        $parsed = RuleParser::parseRules([
            'required',
            'is_even' => $fn,
            $fn,
            'between_len' => [3, 11],
            'min_len' => 6,
            ['max_len', 20],
            ['numeric'],
            [],
            'trim|lower_case',
        ]);

        $this->assertSame(
            ['required', 'is_even', 'callback', 'between_len', 'min_len', 'max_len', 'numeric', 'trim', 'lower_case'],
            array_column($parsed, 'name'),
        );
        $this->assertSame(['3', '11'], $parsed[3]['params']);
        $this->assertSame('between_len,3,11', $parsed[3]['raw']);
        $this->assertSame(['6'], $parsed[4]['params']);
        $this->assertSame('min_len,6', $parsed[4]['raw']);
        $this->assertSame(['20'], $parsed[5]['params']);
        $this->assertSame('max_len,20', $parsed[5]['raw']);
        $this->assertSame('numeric', $parsed[6]['raw']);
    }

    public function testParseEmptyStringsAndEmptyTokens(): void
    {
        $this->assertSame([], RuleParser::parseStringRules('   '));
        $this->assertSame(['required', 'max_len'], array_column(RuleParser::parseStringRules('required||max_len,5|'), 'name'));
    }

    // --- RuleEngine / ValidationEngine --------------------------------------

    public function testEmptyWildcardWithRequiredReportsBaseField(): void
    {
        $validator = new Validator();
        $result = $validator->validateData(['items' => []], ['items.*.id' => 'required|integer']);
        $this->assertFalse($result->isValid());
        $this->assertSame('items', $result->getErrors()[0]['field']);
        $this->assertSame('required', $result->getErrors()[0]['rule']);

        // Without "required" an empty wildcard is ignored
        $this->assertTrue($validator->validateData([], ['items.*.id' => 'integer'])->isValid());
    }

    public function testInlineClosureRuleInArrayDefinition(): void
    {
        $validator = new Validator();
        $rules = ['age' => ['required', 'adult' => static fn(string $f, mixed $v): bool => (int) $v >= 18]];

        $this->assertTrue($validator->validateData(['age' => 20], $rules)->isValid());
        $result = $validator->validateData(['age' => 10], $rules);
        $this->assertFalse($result->isValid());
        $this->assertSame('adult', $result->getErrors()[0]['rule']);
    }

    public function testCheckFieldsAllowsNestedRuleParents(): void
    {
        $validator = new Validator();
        $result = $validator->validateData(
            ['user' => ['email' => 'a@b.co'], 'extra' => 1],
            ['user.email' => 'valid_email'],
            [],
            true,
        );

        $this->assertSame(['extra'], array_keys($result->getErrorsArray()));
    }

    public function testValidationEngineExposesCollaborators(): void
    {
        $rules = new RuleRegistry();
        $filters = new FilterRegistry();
        $translator = new Translator();
        $engine = new ValidationEngine($rules, $filters, $translator);

        $this->assertSame($rules, $engine->getRuleRegistry());
        $this->assertSame($filters, $engine->getFilterRegistry());
        $this->assertSame($translator, $engine->getTranslator());
    }

    // --- FilterEngine / registries ------------------------------------------

    public function testFilterSkipsMissingFieldsAndAppliesClosures(): void
    {
        $validator = new Validator();
        $result = $validator->filter(
            ['name' => ' john '],
            [
                'name' => ['trim', static fn(mixed $v): string => strtoupper((string) $v)],
                'missing' => 'trim',
            ],
        );

        $this->assertSame(['name' => 'JOHN'], $result);
    }

    public function testUnknownFilterThrows(): void
    {
        $this->expectException(FilterNotFoundException::class);
        $this->expectExceptionMessage("Filter 'does_not_exist' is not defined or registered.");
        (new Validator())->filter(['a' => 'x'], ['a' => 'does_not_exist']);
    }

    public function testRegistriesExposeAllEntries(): void
    {
        $rules = new RuleRegistry();
        $this->assertTrue($rules->has('required'));
        $this->assertFalse($rules->has('nope'));
        $this->assertArrayHasKey('required', $rules->all());

        $filters = new FilterRegistry();
        $this->assertTrue($filters->has('trim'));
        $this->assertFalse($filters->has('nope'));
        $this->assertArrayHasKey('trim', $filters->all());
    }

    // --- ValidationResult / MessageBag --------------------------------------

    public function testValidationResultArrayAccessAndErrors(): void
    {
        $bag = (new MessageBag())->add('a', 'required', 'A is required');
        $result = new ValidationResult(['a' => 1], $bag);

        $this->assertTrue(isset($result['a']));
        $this->assertFalse(isset($result['b']));
        $this->assertNull($result['b']);

        $result['b'] = 2;
        $result[] = 3;
        unset($result['a']);
        $this->assertSame(['b' => 2, 0 => 3], $result->getData());

        $this->assertCount(1, $result->getErrors());
        $this->assertSame(['A is required'], $result->getReadableErrors());
        $this->assertSame($bag, $result->getMessageBag());
    }

    public function testMessageBagQueriesAndHtml(): void
    {
        $bag = new MessageBag();
        $this->assertFalse($bag->has());
        $this->assertSame('', $bag->getReadableErrors(true));

        $bag->add('email', 'valid_email', 'Bad <email>')->add('email', 'required', 'Second')->add('name', 'required', 'Name');
        $this->assertTrue($bag->has());
        $this->assertTrue($bag->has('name'));
        $this->assertFalse($bag->has('age'));
        $this->assertSame(['email' => 'Bad <email>', 'name' => 'Name'], $bag->getErrorsArray());
        $this->assertSame(
            '<ul class="errs&quot;"><li>Bad &lt;email&gt;</li><li>Second</li><li>Name</li></ul>',
            $bag->getReadableErrors(true, 'field', 'errs"'),
        );
        $this->assertCount(3, $bag);

        $bag->clear();
        $this->assertCount(0, $bag);
    }

    // --- Translator ---------------------------------------------------------

    public function testTranslatorCustomMessageResolutionOrder(): void
    {
        $translator = new Translator();
        $translator->setCustomMessages([
            'email' => 'Email is wrong',
            'name.required' => 'Name please',
            'min_len' => 'Too short: {field}',
        ]);

        $this->assertSame('Email is wrong', $translator->trans('required', [], 'email'));
        $this->assertSame('Name please', $translator->trans('required', [], 'name'));
        $this->assertSame('Too short: Bio', $translator->trans('min_len', ['{field}' => 'Bio'], 'bio'));
        $this->assertSame('The X field is invalid.', $translator->trans('totally_unknown_rule', ['{field}' => 'X']));
    }

    // --- Validator static API / facade --------------------------------------

    public function testValidatorInstanceSettersAndAccessors(): void
    {
        $validator = new Validator();
        $validator->set_fields_error_messages(['code' => ['required' => 'Code missing']]);
        $validator->setFieldName('first_name', 'Given name');
        $validator->setFieldNames(['last_name' => 'Family name']);
        $validator->addTranslations('fr', ['required' => 'Le champ {field} est requis.']);
        $validator->setLocale('fr');

        $this->assertFalse($validator->run([], ['code' => 'required', 'first_name' => 'required', 'last_name' => 'required']));
        $this->assertSame('Code missing', $validator->errors()[0]['message']);
        $this->assertSame('Le champ Given name est requis.', $validator->errors()[1]['message']);
        $this->assertSame('Le champ Family name est requis.', $validator->get_errors_array()['last_name']);
        $this->assertSame('fr', $validator->getTranslator()->getLocale());
    }

    public function testValidatorStaticRegistrationApi(): void
    {
        Validator::add_validator('core_edge_is_six', static fn(string $f, mixed $v): bool => (int) $v === 6, 'Must be six');
        Validator::add_filter('core_edge_double', static fn(mixed $v): int => (int) $v * 2);

        $this->assertTrue(Validator::has_validator('core_edge_is_six'));
        $this->assertTrue(Validator::has_filter('core_edge_double'));
        $this->assertFalse(Validator::has_filter('core_edge_missing'));

        $this->assertSame(['n' => 6], (new Validator())->run(['n' => '3.5'], ['n' => 'core_edge_is_six'], ['n' => 'whole_number|core_edge_double']));
    }

    public function testValidatorStaticGlobalTranslatorSetters(): void
    {
        Validator::set_field_name('core_edge_field', 'Core Edge');
        Validator::set_field_names(['core_edge_other' => 'Other Edge']);
        Validator::set_error_message('core_edge_rule', 'Edge message');
        Validator::set_error_messages(['core_edge_rule2' => 'Edge message 2']);

        $translator = Validator::getGlobalTranslator();
        $this->assertSame('Core Edge', $translator->getFieldName('core_edge_field'));
        $this->assertSame('Other Edge', $translator->getFieldName('core_edge_other'));
        $this->assertSame('Edge message', $translator->trans('core_edge_rule'));
        $this->assertSame('Edge message 2', $translator->trans('core_edge_rule2'));
    }

    public function testStaticSettersApplyToValidatorInstances(): void
    {
        Validator::add_validator('static_setter_never', static fn(): bool => false);
        Validator::set_field_name('static_setter_a', 'Static Alpha');
        Validator::set_error_message('static_setter_never', '{field} rejected globally');
        Validator::set_error_messages(['static_setter_b' => ['required' => 'B globally required']]);

        // Static GUMP API
        $this->assertSame(
            ['Static Alpha rejected globally'],
            Validator::is_valid(['static_setter_a' => 'x'], ['static_setter_a' => 'static_setter_never']),
        );

        // Instances created before or after the static call both see the global settings
        $validator = new Validator('vi');
        Validator::set_field_name('static_setter_c', 'Static Charlie');
        $this->assertSame(
            ['static_setter_b' => 'B globally required', 'static_setter_c' => 'Static Charlie rejected globally'],
            $validator->validate(
                ['static_setter_c' => 'x'],
                ['static_setter_b' => 'required', 'static_setter_c' => 'static_setter_never'],
            ),
        );

        // Instance-level settings take precedence over global ones
        $validator->setFieldName('static_setter_a', 'Local Alpha');
        $validator->set_fields_error_messages(['static_setter_b' => ['min_len' => 'B local min']]);
        $this->assertSame(
            ['static_setter_a' => 'Local Alpha rejected globally', 'static_setter_b' => 'B globally required'],
            $validator->validate(
                ['static_setter_a' => 'x'],
                ['static_setter_a' => 'static_setter_never', 'static_setter_b' => 'required'],
            ),
        );
        $this->assertSame(
            ['static_setter_b' => 'B local min'],
            $validator->validate(['static_setter_b' => 'x'], ['static_setter_b' => 'min_len,3']),
        );
    }

    public function testTranslatorParentMergesFieldNamesAndMessages(): void
    {
        $parent = (new Translator())
            ->setFieldNames(['items.*.price' => 'Item price', 'name' => 'Parent name'])
            ->setCustomMessages(['email' => ['required' => 'Parent email required', 'valid_email' => 'Parent email invalid']]);
        $child = (new Translator('vi', $parent))
            ->setFieldName('name', 'Child name')
            ->setCustomMessages(['email' => ['required' => 'Child email required']]);

        $this->assertSame('Item price', $child->getFieldName('items.3.price'));
        $this->assertSame('Child name', $child->getFieldName('name'));
        $this->assertSame('Parent name', $parent->getFieldName('name'));
        $this->assertSame('Child email required', $child->trans('required', [], 'email'));
        $this->assertSame('Parent email invalid', $child->trans('valid_email', [], 'email'));
    }

    public function testFacadeCreateAndIsValid(): void
    {
        $this->assertInstanceOf(Validator::class, PhalconValidation::create('vi'));
        $this->assertSame('vi', PhalconValidation::create('vi')->getTranslator()->getLocale());

        $this->assertTrue(PhalconValidation::isValid(['a' => 'x'], ['a' => 'required']));
        $this->assertSame(
            ['A is needed'],
            PhalconValidation::isValid([], ['a' => 'required'], ['a' => ['required' => 'A is needed']]),
        );
    }
}
