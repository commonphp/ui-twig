# Testing

## Required dev dependencies

This package uses PHPUnit 13 for its test suite. `composer.json` already lists:

- `phpunit/phpunit:^13.1`

If PHPUnit is missing from a clone, install it with:

```bash
composer require --dev phpunit/phpunit:^13.1
```

## Running tests

Install dependencies for this repository, then run PHPUnit from this repository root:

```bash
composer install
vendor/bin/phpunit -c drivers/ui/twig/phpunit.xml.dist
```

On Windows, use `vendor\bin\phpunit.bat`.

## Notes

The suite verifies that the Twig driver conforms to `comphp/ui` renderer contracts:

- template lookup by dotted names, direct file paths, `.twig`, and `.html.twig`;
- component registry rendering through the `component()` Twig function;
- view rendering with optional layouts and custom layout content keys;
- Twig-specific configuration and render failure wrapping.
