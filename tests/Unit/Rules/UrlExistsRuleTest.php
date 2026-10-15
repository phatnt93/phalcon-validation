<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Rules\Network\UrlExistsRule;
use PhalconValidation\Tests\Unit\Fixtures\StubUrlExistsRule;
use PhalconValidation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Network-free tests: DNS resolution and the HTTP request are stubbed.
 */
class UrlExistsRuleTest extends TestCase
{
    /**
     * @return array<string, array{string, int|null, bool}>
     */
    public static function statusProvider(): array
    {
        return [
            'status 200' => ['https://example.com/', 200, true],
            'status 204' => ['https://example.com/', 204, true],
            '301 not followed but reachable' => ['http://example.com/', 301, true],
            'status 404' => ['https://example.com/missing', 404, false],
            'status 500' => ['https://example.com/', 500, false],
            'no response' => ['https://example.com/', null, false],
        ];
    }

    #[DataProvider('statusProvider')]
    public function testStatusCodes(string $url, ?int $status, bool $expected): void
    {
        $rule = new StubUrlExistsRule(['example.com' => ['93.184.216.34']], $status);
        $this->assertSame($expected, $rule->validate('url', $url, [], []));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blockedUrlProvider(): array
    {
        return [
            'ftp scheme' => ['ftp://example.com/file'],
            'file scheme' => ['file://localhost/etc/passwd'],
            'loopback literal' => ['http://127.0.0.1/admin'],
            'private literal' => ['http://10.0.0.1/'],
            'metadata endpoint' => ['http://169.254.169.254/latest/meta-data/'],
            'ipv6 loopback' => ['http://[::1]/'],
            'host resolving to private ip' => ['http://intranet.example.com/'],
            'host with mixed public and private records' => ['http://mixed.example.com/'],
            'unresolvable host' => ['http://nowhere.invalid/'],
            'not a url' => ['not a url'],
        ];
    }

    #[DataProvider('blockedUrlProvider')]
    public function testBlockedUrlsAreRejectedWithoutRequest(string $url): void
    {
        $rule = new StubUrlExistsRule([
            'intranet.example.com' => ['192.168.1.10'],
            'mixed.example.com' => ['93.184.216.34', '10.1.2.3'],
        ]);

        $this->assertFalse($rule->validate('url', $url, [], []));
        $this->assertNull($rule->requestedUrl, 'No HTTP request may be sent for a blocked URL');
    }

    public function testAllowPrivateParameterSkipsAddressCheck(): void
    {
        $rule = new StubUrlExistsRule();

        $this->assertTrue($rule->validate('url', 'http://10.0.0.1/health', ['allow_private'], []));
        $this->assertSame('http://10.0.0.1/health', $rule->requestedUrl);
        $this->assertFalse($rule->validate('url', 'ftp://10.0.0.1/', ['allow_private'], []));
    }

    public function testNonStringValueIsRejected(): void
    {
        $this->assertFalse((new StubUrlExistsRule())->validate('url', ['https://example.com'], [], []));
    }

    public function testIpLiteralIsResolvedToItself(): void
    {
        $rule = new class extends UrlExistsRule {
            /**
             * @return list<string>
             */
            public function resolve(string $host): array
            {
                return $this->resolveHost($host);
            }
        };

        $this->assertSame(['8.8.8.8'], $rule->resolve('8.8.8.8'));
        $this->assertSame(['::1'], $rule->resolve('::1'));
    }

    /**
     * @return array<string, array{array<int|string, mixed>, int|null}>
     */
    public static function headerProvider(): array
    {
        return [
            'http 1.1' => [['HTTP/1.1 200 OK'], 200],
            'http 2' => [['HTTP/2 404'], 404],
            'reason containing 200' => [['HTTP/1.1 404 Not Found 200'], 404],
            'garbage' => [['200 OK'], null],
            'empty' => [[], null],
            'non-string' => [[200], null],
        ];
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    #[DataProvider('headerProvider')]
    public function testParseStatusCode(array $headers, ?int $expected): void
    {
        $this->assertSame($expected, UrlExistsRule::parseStatusCode($headers));
    }

    public function testPrivateOrReservedDetection(): void
    {
        $this->assertTrue(UrlExistsRule::isPrivateOrReserved('127.0.0.1'));
        $this->assertTrue(UrlExistsRule::isPrivateOrReserved('172.16.0.5'));
        $this->assertTrue(UrlExistsRule::isPrivateOrReserved('fd00::1'));
        $this->assertTrue(UrlExistsRule::isPrivateOrReserved('not-an-ip'));
        $this->assertFalse(UrlExistsRule::isPrivateOrReserved('8.8.8.8'));
        $this->assertFalse(UrlExistsRule::isPrivateOrReserved('2001:4860:4860::8888'));
    }
}
