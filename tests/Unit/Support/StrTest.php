<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Support;

use PhalconValidation\Support\Str;
use PhalconValidation\Tests\TestCase;

class StrTest extends TestCase
{
    public function testHumanize(): void
    {
        $this->assertSame('User First Name', Str::humanize('user_first_name'));
        $this->assertSame('Customer Profile Age', Str::humanize('customer.profile.age'));
    }

    public function testSlug(): void
    {
        $this->assertSame('tieu-de-bai-viet', Str::slug('Tiêu Đề Bài Viết'));
        $this->assertSame('hello-world', Str::slug('Hello World!'));
    }

    public function testCaseConversions(): void
    {
        $this->assertSame('hello_world', Str::snake('helloWorld'));
        $this->assertSame('helloWorld', Str::camel('hello_world'));
    }
}
