<?php

declare(strict_types=1);

namespace PhalconValidation\Adapters\Phalcon;

use Phalcon\Di\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use PhalconValidation\Validator;

/**
 * Service provider registering the 'validator' service into Phalcon DI container.
 */
class ValidationServiceProvider implements ServiceProviderInterface
{
    protected string $serviceName = 'validator';

    public function __construct(string $serviceName = 'validator')
    {
        $this->serviceName = $serviceName;
    }

    public function register(DiInterface $di): void
    {
        $di->setShared($this->serviceName, function () {
            return new Validator();
        });
    }
}
