<?php

declare(strict_types=1);

namespace PhalconValidation\Tests;

use Phalcon\Filter\Validation;
use Phalcon\Messages\Messages;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Runs a native Phalcon validation and narrows its result to Messages.
     *
     * Newer ext-phalcon builds declare validate() as returning Messages|bool.
     *
     * @param array<string, mixed> $data
     */
    protected function nativeValidate(Validation $validation, array $data): Messages
    {
        $messages = $validation->validate($data);
        $this->assertInstanceOf(Messages::class, $messages);

        return $messages;
    }
}
