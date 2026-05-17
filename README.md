# CommonPHP Twig UI Driver

UI driver for CommonPHP that wraps Twig for output rendering and reusable component templates.

## Requirements

- PHP `^8.5`
- `comphp/ui:^0.3`
- `twig/twig`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/ui-twig
```

## Usage

```php
<?php

// TODO: Write usage
```

## Driver Notes

This driver is intended to let CommonPHP UI render components, layouts, and interface elements through Twig while keeping the core UI package rendering-engine neutral.

Global components should be provided through the driver or its configuration so applications can share consistent UI primitives without locking the UI package to Twig directly.

## Error Handling

Template lookup, rendering, component, configuration, and driver failures should throw CommonPHP UI driver exceptions instead of returning ambiguous false values.

## Documentation

- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
