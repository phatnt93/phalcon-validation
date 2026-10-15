<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Core;

use PhalconValidation\Core\RuleParser;
use PhalconValidation\Tests\TestCase;

class RuleParserTest extends TestCase
{
    public function testParsePipeStringRules(): void
    {
        $rules = 'required|min_len,6|between_len,3;11';
        $parsed = RuleParser::parseRules($rules);

        $this->assertCount(3, $parsed);
        $this->assertSame('required', $parsed[0]['name']);
        $this->assertSame([], $parsed[0]['params']);

        $this->assertSame('min_len', $parsed[1]['name']);
        $this->assertSame(['6'], $parsed[1]['params']);

        $this->assertSame('between_len', $parsed[2]['name']);
        $this->assertSame(['3', '11'], $parsed[2]['params']);
    }

    public function testParseRegexWithPipeInside(): void
    {
        $rules = 'required|regex,/^[A-Z|0-9]+$/|max_len,50';
        $parsed = RuleParser::parseRules($rules);

        $this->assertCount(3, $parsed);
        $this->assertSame('required', $parsed[0]['name']);
        $this->assertSame('regex', $parsed[1]['name']);
        $this->assertSame(['/^[A-Z|0-9]+$/'], $parsed[1]['params']);
        $this->assertSame('max_len', $parsed[2]['name']);
        $this->assertSame(['50'], $parsed[2]['params']);
    }

    public function testParseArrayRules(): void
    {
        $rules = [
            'required',
            ['min_len', 5],
            'regex' => '/^[a-z]+$/',
        ];
        $parsed = RuleParser::parseRules($rules);

        $this->assertCount(3, $parsed);
        $this->assertSame('required', $parsed[0]['name']);
        $this->assertSame('min_len', $parsed[1]['name']);
        $this->assertSame(['5'], $parsed[1]['params']);
        $this->assertSame('regex', $parsed[2]['name']);
        $this->assertSame(['/^[a-z]+$/'], $parsed[2]['params']);
    }
}
