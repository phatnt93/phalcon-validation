# Contributing

Thanks for your interest in improving `phatnt93/phalcon-validation`!

## Development setup

Requirements: PHP 8.1+ (CLI), Composer 2, and optionally `ext-phalcon` 5.x to run integration tests.

```bash
git clone https://github.com/phatnt93/phalcon-validation.git
cd phalcon-validation
composer install
```

## Checks to run before opening a pull request

```bash
composer validate --strict
composer test        # PHPUnit (integration tests are skipped without ext-phalcon)
composer analyse     # PHPStan
composer cs:check    # PHP-CS-Fixer (PER-CS 2.0), use `composer cs:fix` to apply
```

All four must pass; CI runs the same commands on PHP 8.1 – 8.4 with both lowest and highest dependency versions.

## Coding conventions

- Every PHP file starts with `declare(strict_types=1);`.
- Full type hints on properties, parameters and return types; detailed array shapes in PHPDoc.
- One rule / filter per class, implementing the interfaces in `src/Contracts/`.
- Exceptions live in `src/Exceptions/`, extend the matching SPL exception and expose a named constructor (e.g. `RuleNotFoundException::forRule()`).
- Every new rule or filter needs tests for valid, invalid and edge-case input.

## Translations (i18n)

- `en` is the default locale and the source of truth; `vi` is the second shipped locale.
- User-facing messages live in `src/I18n/{locale}.php` as `rule => template` arrays. A rule's `$defaultMessage` is only an English fallback for keys missing from every dictionary; always add the real message to the locale files and never build user-facing text anywhere else in a rule class.
- Every locale must have exactly the same keys and placeholders (`{field}`, `{param}`, `{param:0}`...) as `en.php`. `tests/Unit/I18n/LocaleParityTest.php` enforces this.
- Exception messages, PHPDoc, comments and commit messages are written in English only.
- When you change `README.md`, update `README.vi.md` in the same pull request.

### Adding a new locale

1. Copy `src/I18n/en.php` to `src/I18n/{locale}.php` and translate the values, keeping keys and placeholders.
2. Add the locale to `Translator::SUPPORTED_LOCALES`.
3. Update the language lists in `README.md`, `README.vi.md` and `llms-full.txt`.
4. Add an entry under `Added` in `CHANGELOG.md`.

## Versioning and changelog

This project follows [Semantic Versioning](https://semver.org/). Add your change to the `Unreleased` section of `CHANGELOG.md`.

- Changing a translation key or placeholder, a callback signature or a public API is a breaking change (major release).
- Deprecate before removing: mark the API `@deprecated`, trigger `E_USER_DEPRECATED`, and remove it only in the next major version.

## Reporting security issues

Please do not open public issues for vulnerabilities. Follow [SECURITY.md](SECURITY.md).
