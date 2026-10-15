<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Support;

use ArrayObject;
use PhalconValidation\Support\Arr;
use PhalconValidation\Support\Str;
use PhalconValidation\Tests\TestCase;

class SupportEdgeCasesTest extends TestCase
{
    public function testSnakeKeepsAlreadyLowercaseValue(): void
    {
        $this->assertSame('already', Str::snake('already'));
        $this->assertSame('user-name', Str::snake('UserName', '-'));
    }

    public function testRemoveVietnameseAccents(): void
    {
        $this->assertSame('Tieng Viet co dau Dd', Str::removeVietnameseAccents('Tiếng Việt có dấu Đđ'));
        $this->assertSame('AEIOUY aeiouy', Str::removeVietnameseAccents('ẤỀÌỔỨỲ ấềìổứỳ'));
    }

    public function testCaseAndSubstringHelpers(): void
    {
        $this->assertSame('đông', Str::lower('ĐÔNG'));
        $this->assertSame('ĐÔNG', Str::upper('đông'));

        $this->assertTrue(Str::startsWith('hello', ''));
        $this->assertTrue(Str::startsWith('hello', 'he'));
        $this->assertFalse(Str::startsWith('hello', 'lo'));

        $this->assertTrue(Str::endsWith('hello', ''));
        $this->assertTrue(Str::endsWith('hello', 'lo'));
        $this->assertFalse(Str::endsWith('hello', 'he'));

        $this->assertTrue(Str::contains('hello', ''));
        $this->assertTrue(Str::contains('hello', 'ell'));
        $this->assertFalse(Str::contains('hello', 'xyz'));
    }

    public function testArrGetWithNullKeyAndArrayAccess(): void
    {
        $data = ['a' => 1];
        $this->assertSame($data, Arr::get($data, null));

        $nested = ['user' => new ArrayObject(['profile' => ['age' => 30]])];
        $this->assertSame(30, Arr::get($nested, 'user.profile.age'));
        $this->assertSame('x', Arr::get($nested, 'user.missing', 'x'));
    }

    public function testArrHasWithArrayAccess(): void
    {
        $nested = ['user' => new ArrayObject(['email' => 'a@b.c'])];
        $this->assertTrue(Arr::has($nested, 'user.email'));
        $this->assertFalse(Arr::has($nested, 'user.phone'));
    }

    public function testArrSetWithNullKeyReplacesArray(): void
    {
        $data = ['a' => 1];
        Arr::set($data, null, ['b' => 2]);
        $this->assertSame(['b' => 2], $data);

        Arr::set($data, null, 'scalar');
        $this->assertSame(['scalar'], $data);
    }

    public function testArrIsAssoc(): void
    {
        $this->assertFalse(Arr::isAssoc([]));
        $this->assertFalse(Arr::isAssoc(['a', 'b']));
        $this->assertTrue(Arr::isAssoc(['a' => 1]));
        $this->assertTrue(Arr::isAssoc([1 => 'a']));
    }
}
