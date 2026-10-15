<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Filters;

use PhalconValidation\Filters\BasicTagsFilter;
use PhalconValidation\Filters\BooleanFilter;
use PhalconValidation\Filters\HtmlencodeFilter;
use PhalconValidation\Filters\LowercaseFilter;
use PhalconValidation\Filters\MsWordCharactersFilter;
use PhalconValidation\Filters\NoiseWordsFilter;
use PhalconValidation\Filters\RmpunctuationFilter;
use PhalconValidation\Filters\SanitizeEmailFilter;
use PhalconValidation\Filters\SanitizeFloatsFilter;
use PhalconValidation\Filters\SanitizeNumbersFilter;
use PhalconValidation\Filters\SanitizeStringFilter;
use PhalconValidation\Filters\SlugFilter;
use PhalconValidation\Filters\TrimFilter;
use PhalconValidation\Filters\UppercaseFilter;
use PhalconValidation\Filters\UrlencodeFilter;
use PhalconValidation\Filters\WholeNumberFilter;
use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;

class FiltersTest extends TestCase
{
    public function testTrimFilter(): void
    {
        $filter = new TrimFilter();
        $this->assertSame('hello', $filter->filter('  hello   '));
        $this->assertSame('world', $filter->filter('/world/', ['/']));
    }

    public function testSanitizeStringFilter(): void
    {
        $filter = new SanitizeStringFilter();
        $input = '<script>alert("xss")</script><b>Hello</b> World!';
        $this->assertSame('Hello World!', $filter->filter($input));
    }

    public function testSanitizeEmailFilter(): void
    {
        $filter = new SanitizeEmailFilter();
        $input = 'john (comment) @example .com';
        $this->assertSame('johncomment@example.com', $filter->filter($input));
    }

    public function testSanitizeNumbersFilter(): void
    {
        $filter = new SanitizeNumbersFilter();
        $this->assertSame('+123456', $filter->filter('+123abc456'));
        $this->assertSame('+123--456', $filter->filter('+123-abc-456'));
    }

    public function testSanitizeFloatsFilter(): void
    {
        $filter = new SanitizeFloatsFilter();
        $this->assertSame('-123.45', $filter->filter('-123.45 abc'));
    }

    public function testBooleanFilter(): void
    {
        $filter = new BooleanFilter();
        $this->assertTrue($filter->filter('1'));
        $this->assertTrue($filter->filter(1));
        $this->assertTrue($filter->filter('true'));
        $this->assertTrue($filter->filter(true));
        $this->assertTrue($filter->filter('yes'));
        $this->assertTrue($filter->filter('on'));

        $this->assertFalse($filter->filter('0'));
        $this->assertFalse($filter->filter(0));
        $this->assertFalse($filter->filter('false'));
        $this->assertFalse($filter->filter('no'));
        $this->assertFalse($filter->filter('random'));
    }

    public function testBasicTagsFilter(): void
    {
        $filter = new BasicTagsFilter();
        $input = '<p><b>Hello</b> <script>danger</script> <iframe src="bad"></iframe></p>';
        $this->assertSame('<p><b>Hello</b> danger </p>', $filter->filter($input));
    }

    public function testWholeNumberFilter(): void
    {
        $filter = new WholeNumberFilter();
        $this->assertSame(42, $filter->filter('42.8'));
        $this->assertSame(-15, $filter->filter('-15.9'));
        $this->assertSame(0, $filter->filter('abc'));
    }

    public function testMsWordCharactersFilter(): void
    {
        $filter = new MsWordCharactersFilter();
        // “Hello” & ‘World’ – test…
        $input = "\xe2\x80\x9cHello\xe2\x80\x9d & \xe2\x80\x98World\xe2\x80\x99 \xe2\x80\x93 test\xe2\x80\xa6";
        $expected = '"Hello" & \'World\' - test...';
        $this->assertSame($expected, $filter->filter($input));
    }

    public function testCaseFilters(): void
    {
        $lower = new LowercaseFilter();
        $upper = new UppercaseFilter();

        $this->assertSame('xin chào việt nam', $lower->filter('XIN CHÀO VIỆT NAM'));
        $this->assertSame('XIN CHÀO VIỆT NAM', $upper->filter('xin chào việt nam'));
    }

    public function testSlugFilter(): void
    {
        $filter = new SlugFilter();
        $this->assertSame('tieu-de-bai-viet', $filter->filter('Tiêu Đề Bài Viết'));
        $this->assertSame('hello_world', $filter->filter('Hello World', ['_']));
    }

    public function testUrlencodeAndHtmlencode(): void
    {
        $urlencode = new UrlencodeFilter();
        $htmlencode = new HtmlencodeFilter();

        $this->assertSame('hello+world%21', $urlencode->filter('hello world!'));
        $this->assertSame('&lt;b&gt;&quot;test&quot;&lt;/b&gt;', $htmlencode->filter('<b>"test"</b>'));
    }

    public function testRmpunctuationFilter(): void
    {
        $filter = new RmpunctuationFilter();
        $this->assertSame('Hello World', $filter->filter('Hello, World!'));
    }

    public function testNoiseWordsFilter(): void
    {
        $filter = new NoiseWordsFilter();
        $this->assertSame('quick brown fox jumps lazy dog', $filter->filter('the quick brown fox jumps over the lazy dog'));
    }

    public function testFiltersThroughValidatorPipeline(): void
    {
        $validator = new Validator();
        $validator->filter_rules([
            'username' => 'trim|lower_case',
            'is_active' => 'boolean',
            'amount' => 'whole_number',
        ]);
        $validator->validation_rules([
            'username' => 'required',
        ]);

        $input = [
            'username' => '  PHAT_NGUYEN  ',
            'is_active' => 'on',
            'amount' => '-100.5',
        ];

        $clean = $validator->run($input);
        $this->assertIsArray($clean);
        $this->assertSame('phat_nguyen', $clean['username']);
        $this->assertTrue($clean['is_active']);
        $this->assertSame(-100, $clean['amount']);
    }
}
