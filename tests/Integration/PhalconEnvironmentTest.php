<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Integration;

use Phalcon\Support\Version;
use PhalconValidation\Tests\TestCase;

class PhalconEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('phalcon')) {
            $this->markTestSkipped('Phalcon extension is not loaded in this environment.');
        }
    }

    public function testPhalconExtensionLoaded(): void
    {
        $this->assertTrue(extension_loaded('phalcon'), 'Phalcon extension must be loaded');

        $version = new Version();
        $this->assertNotEmpty($version->get());
        $this->assertStringStartsWith('5.', $version->get());
    }
}
