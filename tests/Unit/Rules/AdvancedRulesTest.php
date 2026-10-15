<?php

declare(strict_types=1);

namespace PhalconValidation\Tests\Unit\Rules;

use PhalconValidation\Tests\TestCase;
use PhalconValidation\Validator;

class AdvancedRulesTest extends TestCase
{
    public function testNetworkRules(): void
    {
        $rules = [
            'email' => 'valid_email',
            'website' => 'valid_url',
            'ip' => 'valid_ip',
            'ipv4' => 'valid_ipv4',
            'ipv6' => 'valid_ipv6',
            'mac' => 'mac_address',
            'domain' => 'domain_name',
            'port' => 'port_number',
            'handle' => 'social_handle',
            'name' => 'valid_name',
        ];

        $validData = [
            'email' => 'test@example.com',
            'website' => 'https://example.com/api',
            'ip' => '192.168.1.1',
            'ipv4' => '10.0.0.1',
            'ipv6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'mac' => '00:1A:2B:3C:4D:5E',
            'domain' => 'example.com',
            'port' => 8080,
            'handle' => '@phatnguyen_93',
            'name' => "Nguyễn Tấn Phát",
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        $invalidData = [
            'email' => 'not-an-email',
            'website' => 'not a url',
            'ip' => '999.999.999.999',
            'ipv4' => '2001:db8::1',
            'ipv6' => '192.168.1.1',
            'mac' => 'invalid-mac',
            'domain' => 'http://example.com',
            'port' => 70000,
            'handle' => '@invalid handle with spaces',
            'name' => 'Phat<script>alert(1)</script>',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(10, $res);
    }

    public function testDateTimeRules(): void
    {
        $rules = [
            'birth_date' => 'date,Y-m-d|min_age,18',
            'range' => 'date_range,2024-01-01;2024-12-31',
            'future' => 'future_date',
            'past' => 'past_date',
            'business' => 'business_day',
            'time' => 'valid_time',
        ];

        // 2024-05-15 is Wednesday (Business day)
        $validData = [
            'birth_date' => '1995-05-15',
            'range' => '2024-06-15',
            'future' => date('Y-m-d', strtotime('+3 days')),
            'past' => date('Y-m-d', strtotime('-3 days')),
            'business' => '2024-05-15',
            'time' => '14:30:00',
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        // 2024-05-19 is Sunday (Non-business day)
        $invalidData = [
            'birth_date' => date('Y-m-d', strtotime('-5 years')), // Underage
            'range' => '2025-01-01',
            'future' => date('Y-m-d', strtotime('-1 day')),
            'past' => date('Y-m-d', strtotime('+1 day')),
            'business' => '2024-05-19',
            'time' => '25:00',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(6, $res);
    }

    public function testGeoAndIsoRules(): void
    {
        $rules = [
            'lat' => 'latitude',
            'lng' => 'longitude',
            'coord' => 'coordinates',
            'zip' => 'postal_code',
            'address' => 'street_address',
            'country' => 'country_code',
            'currency' => 'currency_code',
            'lang' => 'language_code',
            'tz' => 'timezone',
        ];

        $validData = [
            'lat' => '16.0544',
            'lng' => '108.2022',
            'coord' => '16.0544, 108.2022',
            'zip' => '550000',
            'address' => '123 Nguyen Van Linh, Da Nang',
            'country' => 'VNM',
            'currency' => 'VND',
            'lang' => 'vi',
            'tz' => 'Asia/Ho_Chi_Minh',
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        $invalidData = [
            'lat' => '95.0',
            'lng' => '200.0',
            'coord' => 'not coordinates',
            'zip' => '!@#$',
            'address' => 'NoNumberAddress',
            'country' => 'VIETNAM',
            'currency' => 'DONG',
            'lang' => 'vietnamese',
            'tz' => 'Fake/Timezone',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(9, $res);
    }

    public function testSecurityAndFinanceRules(): void
    {
        $rules = [
            'cc' => 'valid_cc',
            'iban_code' => 'iban',
            'uuid_val' => 'uuid',
            'guid_val' => 'guidv4',
            'jwt' => 'jwt_token',
            'hash_val' => 'hash,md5',
            'b64' => 'base64',
            'pwd' => 'strong_password',
            'clean_sql' => 'no_sql_injection',
            'clean_xss' => 'no_xss',
            'phone' => 'phone_number',
        ];

        // 4532... is a valid Visa test card number (passes Luhn)
        $validData = [
            'cc' => '4532015112830366',
            'iban_code' => 'GB82WEST12345698765432',
            'uuid_val' => '123e4567-e89b-12d3-a456-426614174000',
            'guid_val' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'jwt' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozG4mZJ_E_t1Knu_n4A4o3_5vQ',
            'hash_val' => md5('test'),
            'b64' => base64_encode('Hello world'),
            'pwd' => 'Secret@12345',
            'clean_sql' => 'John Doe',
            'clean_xss' => 'Safe user comment',
            'phone' => '+84905123456',
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        $invalidData = [
            'cc' => '4532015112830367', // Invalid Luhn
            'iban_code' => 'GB82WEST12345698765433', // Invalid check digits
            'uuid_val' => 'not-a-uuid',
            'guid_val' => '123e4567-e89b-12d3-a456-426614174000', // version is 1, not 4
            'jwt' => 'not.jwt',
            'hash_val' => 'not_a_md5_hash',
            'b64' => 'invalid base64 !!@@',
            'pwd' => 'weak',
            'clean_sql' => "admin' OR '1'='1",
            'clean_xss' => '<script>alert("hack")</script>',
            'phone' => 'abc-xyz',
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(11, $res);
    }

    public function testColorContentAndFileRules(): void
    {
        $rules = [
            'hex' => 'hex_color',
            'rgb' => 'rgb_color',
            'status' => 'contains,active;inactive;pending',
            'role' => 'contains_list,admin;manager;editor',
            'username' => 'doesnt_contain_list,root;admin;system',
            'json' => 'valid_json_string',
            'items_eq' => 'valid_array_size_equal,3',
            'items_gte' => 'valid_array_size_greater,2',
            'items_lte' => 'valid_array_size_lesser,5',
            'doc' => 'extension,pdf;docx',
        ];

        $validData = [
            'hex' => '#FF5733',
            'rgb' => 'rgb(255, 87, 51)',
            'status' => 'active',
            'role' => 'admin',
            'username' => 'phatnguyen',
            'json' => '{"success": true}',
            'items_eq' => [1, 2, 3],
            'items_gte' => [1, 2],
            'items_lte' => [1, 2, 3, 4],
            'doc' => ['name' => 'report.pdf', 'error' => 0],
        ];
        $this->assertTrue(Validator::is_valid($validData, $rules));

        $invalidData = [
            'hex' => 'ZZZZZZ',
            'rgb' => 'rgb(999, 999, 999)',
            'status' => 'deleted',
            'role' => 'guest',
            'username' => 'root',
            'json' => '{malformed json',
            'items_eq' => [1, 2],
            'items_gte' => [1],
            'items_lte' => [1, 2, 3, 4, 5, 6],
            'doc' => ['name' => 'malicious.exe', 'error' => 0],
        ];
        $res = Validator::is_valid($invalidData, $rules);
        $this->assertIsArray($res);
        $this->assertCount(10, $res);
    }
}
