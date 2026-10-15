<?php

declare(strict_types=1);

namespace PhalconValidation\Rules\Network;

use PhalconValidation\Rules\AbstractRule;

/**
 * Validates that an http(s) URL is reachable (responds with a 2xx or 3xx status to a HEAD request).
 *
 * Because the URL comes from user input, the rule guards against SSRF by default:
 * - only `http` and `https` are allowed;
 * - hosts resolving to private or reserved addresses (10.0.0.0/8, 127.0.0.1, 169.254.169.254, ::1...)
 *   are rejected unless the `allow_private` parameter is given (`url_exists,allow_private`);
 * - redirects are not followed (a 3xx response counts as reachable) and the request times out after
 *   {@see self::TIMEOUT} seconds.
 *
 * DNS can still change between the check and the request (DNS rebinding); do not rely on this rule
 * as the only network boundary.
 */
class UrlExistsRule extends AbstractRule
{
    public const TIMEOUT = 5.0;

    protected string $name = 'url_exists';
    protected string $defaultMessage = 'The {field} URL does not exist or is unreachable.';

    public function validate(string $field, mixed $value, array $params, array $data): bool
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host = trim((string) parse_url($value, PHP_URL_HOST), '[]');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (!in_array('allow_private', $params, true)) {
            $addresses = $this->resolveHost($host);
            if ($addresses === []) {
                return false;
            }
            foreach ($addresses as $address) {
                if (self::isPrivateOrReserved($address)) {
                    return false;
                }
            }
        }

        $status = $this->fetchStatusCode($value);
        return $status !== null && $status >= 200 && $status < 400;
    }

    /**
     * Parse the status code from the first line of an HTTP response header list.
     *
     * @param array<int|string, mixed> $headers
     */
    public static function parseStatusCode(array $headers): ?int
    {
        $statusLine = $headers[0] ?? null;
        if (!is_string($statusLine) || preg_match('#^HTTP/\S+\s+(\d{3})\b#', $statusLine, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Whether an IP address is in a private or reserved range.
     */
    public static function isPrivateOrReserved(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Resolve a host (or IP literal) to its IPv4/IPv6 addresses.
     *
     * @return list<string>
     */
    protected function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = @gethostbynamel($host) ?: [];
        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($records as $record) {
            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($addresses));
    }

    /**
     * Send a HEAD request without following redirects and return the response status code.
     */
    protected function fetchStatusCode(string $url): ?int
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => self::TIMEOUT,
                'follow_location' => 0,
                'max_redirects' => 1,
                'ignore_errors' => true,
            ],
        ]);

        $headers = @get_headers($url, false, $context);
        return is_array($headers) ? self::parseStatusCode($headers) : null;
    }
}
