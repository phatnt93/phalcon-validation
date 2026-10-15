<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Integration;

use Phalcon\Support\Version;
use PhalconValidation\Tests\TestCase;

class PhalconEnvironmentTest extends TestCase
{
    public function testPhalconExtensionLoaded(): void
    {
        $this->assertTrue(extension_loaded('phalcon'), 'Phalcon extension must be loaded');
        
        $version = new Version();
        $this->assertNotEmpty($version->get());
        $this->assertStringStartsWith('5.', $version->get());
    }
}
