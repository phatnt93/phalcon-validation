# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `Translator::addTranslations()` and `Validator::addTranslations()` to register or override messages for any locale (e.g. `fr`) without modifying the library.
- `Translator::DEFAULT_LOCALE` (`en`) and `Translator::SUPPORTED_LOCALES` (`en`, `vi`) constants.
- Regional locales (e.g. `vi_VN`) fall back to their base language dictionary before `en`.
- `PhalconValidation\Exceptions\InvalidLocaleException`.
- Locale parity test ensuring every shipped dictionary has the same keys and placeholders as `en`.
- PHPStan and PHP-CS-Fixer (PER-CS 2.0) with `composer analyse`, `composer cs:check` and `composer cs:fix` scripts.
- `.gitattributes` (dist archives exclude tests, docs, examples and tooling), `.editorconfig`, `CONTRIBUTING.md`, `AGENTS.md`, `CHANGELOG.md`.
- CI quality job (composer validate, code style, static analysis, coverage) and a `lowest`/`highest` dependency matrix.

### Changed
- Locale codes are normalized (`EN` → `en`, `en-us` → `en_US`).
- `README.vn.md` renamed to `README.vi.md` (ISO 639-1 language code); READMEs now use a language switcher and a dynamic version badge.
- `.github/copilot-instructions.md` and Cursor rules now point to `AGENTS.md`; the legacy `.cursorrules` file was replaced by `.cursor/rules/library.mdc`.
- `composer.json`: `php` constraint simplified to `^8.1`; removed `optimize-autoloader` (no effect for libraries).

### Fixed
- String-based rules (`min_len`, `max_len`, `exact_len`, `between_len`, `word_count`, `starts`, `date_range`, `future_date`, `past_date`, `business_day`, `min_age`, `valid_cc`, `iban`, `contains`, `contains_list`, `doesnt_contain_list`) now fail on array/object input instead of raising PHP warnings or `Error`s. Previously an array was cast to the string `"Array"`, so e.g. `min_len,3` passed for `field[]=x`.
- `sanitize_numbers` and `sanitize_floats` return non-scalar input unchanged and `whole_number` returns `0` for arrays/objects instead of raising errors.
- Static GUMP setters (`Validator::set_field_name()`, `set_field_names()`, `set_error_message()`, `set_error_messages()`) now apply to every `Validator` instance and to `Validator::is_valid()`. Previously they only updated an internal global translator that instances never read. Instance-level settings still take precedence. `Translator` gained an optional `?Translator $parent` constructor argument to support this.
- Phalcon adapter: `valid_ip`, `valid_ipv4` and `valid_ipv6` now match the pure-PHP rules. The native `Ip` validator received `4`/`6` instead of `Ip::VERSION_4`/`Ip::VERSION_6`, so `valid_ipv4` accepted IPv6 addresses (and vice versa), and private/reserved addresses (e.g. `10.0.0.1`, `127.0.0.1`) were rejected.

### Security
- Malformed locale codes (e.g. `../../etc/passwd`) now throw `InvalidLocaleException`, and only shipped locales are loaded from disk. Previously the locale string was concatenated into a `require` path.

## [1.1.0] - 2026-10-15

### Added
- Complete file upload suite: `normalize_files` filter and file validation rules (`extension`, `mimetype`, `min_file_size`, `max_file_size`), with support for native `$_FILES`, normalized arrays and `Phalcon\Http\Request\File`.
- `llms.txt`, `llms-full.txt`, runnable `examples/`, GitHub Copilot instructions, Cursor rules and `SECURITY.md`.
- GitHub Actions workflow testing PHP 8.1 – 8.4.

### Fixed
- Integration tests are skipped when `ext-phalcon` is not available.

## [1.0.1] - 2026-10-15

### Fixed
- PHP 8.1 compatibility: replaced the standalone `true` return type (PHP 8.2+) with `bool|array` in `Validator::validate()`, `Validator::is_valid()` and `PhalconValidation::isValid()`.

## [1.0.0] - 2026-10-15

### Added
- Initial release: GUMP-style validation and sanitization API for Phalcon v5/v6, 80+ validators, 17 filters, nested arrays and wildcards, field aliases, `en`/`vi` messages, Phalcon DI service provider and native `Phalcon\Filter\Validation` bridge.

[Unreleased]: https://github.com/phatnt93/phalcon-validation/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/phatnt93/phalcon-validation/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/phatnt93/phalcon-validation/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/phatnt93/phalcon-validation/releases/tag/v1.0.0
