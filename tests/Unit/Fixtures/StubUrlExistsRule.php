<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Fixtures;

use PhalconValidation\Rules\Network\UrlExistsRule;

/**
 * UrlExistsRule with stubbed DNS resolution and HTTP request, so tests never touch the network.
 */
final class StubUrlExistsRule extends UrlExistsRule
{
    public ?string $requestedUrl = null;

    /**
     * @param array<string, list<string>> $dns Host => resolved addresses
     */
    public function __construct(
        private array $dns = [],
        private ?int $status = 200,
    ) {}

    protected function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return $this->dns[$host] ?? [];
    }

    protected function fetchStatusCode(string $url): ?int
    {
        $this->requestedUrl = $url;
        return $this->status;
    }
}
