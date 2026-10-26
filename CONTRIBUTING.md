# Contributing

Thanks for considering a contribution.

## Development

```bash
composer install
composer test
```

The package is intentionally small: the runtime is plain JavaScript in `resources/js/animated-icons.js`,
the Blade components are in `src/View/Components`, and the registry converter is in `src/Actions`.

## Pull requests

- Add a test when behaviour changes.
- Keep the runtime dependency-free.
- Run `composer test` before opening a PR.
- For a new icon source, include the registry fixture used by the converter test.

## Reporting issues

Include the Laravel/PHP versions, the icon name, the trigger used, and whether the icon was installed
with `animateicons:sync` or hand-authored.
