<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Support;

use PhalconValidation\Support\Arr;
use PhalconValidation\Tests\TestCase;

class ArrTest extends TestCase
{
    public function testGetSimpleAndDotNotation(): void
    {
        $data = [
            'name' => 'John',
            'user' => [
                'profile' => [
                    'email' => 'john@example.com',
                ],
            ],
        ];

        $this->assertSame('John', Arr::get($data, 'name'));
        $this->assertSame('john@example.com', Arr::get($data, 'user.profile.email'));
        $this->assertNull(Arr::get($data, 'user.profile.age'));
        $this->assertSame(25, Arr::get($data, 'user.profile.age', 25));
    }

    public function testSetAndHas(): void
    {
        $data = [];
        Arr::set($data, 'customer.address.city', 'Da Nang');

        $this->assertTrue(Arr::has($data, 'customer.address.city'));
        $this->assertFalse(Arr::has($data, 'customer.address.street'));
        $this->assertSame('Da Nang', Arr::get($data, 'customer.address.city'));
    }

    public function testExpandWildcards(): void
    {
        $data = [
            'items' => [
                ['id' => 1, 'price' => 100],
                ['id' => 2, 'price' => 200],
            ],
        ];

        $paths = Arr::expandWildcards($data, 'items.*.price');
        $this->assertSame(['items.0.price', 'items.1.price'], $paths);

        $emptyPaths = Arr::expandWildcards([], 'items.*.price');
        $this->assertSame([], $emptyPaths);
    }
}
