<img src="https://banners.beyondco.de/Cinder%20Clients.png?theme=light&packageManager=&packageName=&pattern=circuitBoard&style=style_2&description=PHP%2C+Laravel+and+WordPress+clients+for+Cinder.&md=1&showWatermark=0&fontSize=150px&images=fire&widths=500&heights=500">

[![GitHub-Tests](https://github.com/cinderwatch-dev/cinder-clients/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/cinderwatch-dev/cinder-clients/actions/workflows/run-tests.yml)
[![GitHub Code Style](https://github.com/cinderwatch-dev/cinder-clients/actions/workflows/fix-php-code-style-issues.yml/badge.svg?branch=main)](https://github.com/cinderwatch-dev/cinder-clients/actions/workflows/fix-php-code-style-issues.yml)
[![PHPStan](https://github.com/cinderwatch-dev/cinder-clients/actions/workflows/phpstan.yml/badge.svg)](https://github.com/cinderwatch-dev/cinder-clients/actions/workflows/phpstan.yml)

This is the monorepo for the Cinder client packages. **It is the only place you commit and release from.** The package repositories below are read-only mirrors, updated automatically every time a tag is pushed here.

## 📑 Table of Contents

- [Packages](#-packages)
- [Development](#-development)
- [Testing](#-testing)
- [Releasing](#-releasing)
  - [Manual release](#manual-release)
  - [Skipping a release](#skipping-a-release)
  - [Secrets](#secrets)
- [Contributing](#️-contributing)
- [Security Vulnerabilities](#️-security-vulnerabilities)
- [Credits](#-credits)
- [License](#-license)

## 📦 Packages

| Package | Folder | Description |
|---------|--------|-------------|
| [`cinderwatch/cinder-php`](https://packagist.org/packages/cinderwatch/cinder-php) | [`packages/cinder-php`](packages/cinder-php) | Framework-agnostic PHP client |
| [`cinderwatch/cinder-laravel`](https://packagist.org/packages/cinderwatch/cinder-laravel) | [`packages/cinder-laravel`](packages/cinder-laravel) | Laravel adapter |
| [`cinderwatch/cinder-wordpress`](https://packagist.org/packages/cinderwatch/cinder-wordpress) | [`packages/cinder-wordpress`](packages/cinder-wordpress) | WordPress plugin |

The clients talk to Cinder over `/api/v1`, so a breaking change can ship under a new API version without forcing every project to upgrade at once.

## 💻 Development

Install the dependencies from the repository root. The root `composer.json` autoloads all three packages, so changes to `cinder-php` are picked up by the adapters straight away.

```bash
composer install
```

## 🚧 Testing

Run every package's tests:

```bash
composer test
```

Or a single package:

```bash
vendor/bin/pest --testsuite="Cinder PHP"
vendor/bin/pest --testsuite="Cinder Laravel"
vendor/bin/pest --testsuite="Cinder WordPress"
```

CI requires 100% line coverage. Check it locally with PCOV or Xdebug enabled:

```bash
composer test-coverage
```

Static analysis and code style:

```bash
composer analyse
composer format
```

## 🚀 Releasing

```
push packages/* to main
  -> Auto Release: tests (with the coverage check), then tag the monorepo
  -> Split Packages: push each packages/* folder to its mirror with the same tag
  -> Packagist picks up the new tag from each mirror
  -> GitHub Release on cinder-clients, with the WordPress plugin zip attached
```

Pushing to `main` with changes under `packages/**` runs [Auto Release](.github/workflows/auto-release.yml):

1. Runs the Pest suite on PHP 8.3 and 8.4 (8.4 also enforces 100% coverage).
2. Creates the next patch tag on the monorepo, for example `1.0.0` to `1.0.1`.
3. Pushes the tag, which runs [Split Packages](.github/workflows/split.yml) and [Release WordPress plugin](.github/workflows/release-wordpress.yml).

Each adapter requires `cinderwatch/cinder-php` with a caret constraint (`^1.0`), so Auto Release refuses to tag a version the adapters could not install. Bump the constraint in the adapters' `composer.json` before releasing a new major.

### Manual release

- **Actions > Auto Release > Run workflow**, then choose `patch`, `minor` or `major`, or set an exact `version`.
- Or push a tag yourself: `git tag 1.0.1 && git push origin 1.0.1`. This only runs Split Packages.

### Skipping a release

Add `[skip release]` to the commit message when you change files under `packages/` without shipping a new version.

### Secrets

| Secret | Purpose |
|--------|---------|
| `ACCESS_TOKEN` | Fine-grained PAT with `contents: write` on the `cinderwatch-dev` mirror repositories. `GITHUB_TOKEN` cannot push to other repositories or trigger the tag workflows. |

## ✏️ Contributing

Please see [CONTRIBUTING](packages/cinder-php/.github/CONTRIBUTING.md) for details. Open issues and pull requests here, not on the mirrors.

## 🧑‍💻 Security Vulnerabilities

Please review [our security policy](packages/cinder-php/.github/SECURITY.md) on reporting security vulnerabilities.

## 🙏 Credits

- [Rhys Lees](https://github.com/RhysLees)
- [All Contributors](../../contributors)
- [Monorepo Split GitHub Action by Dan Harrin](https://github.com/danharrin/monorepo-split-github-action)
- [Laravel Package Tools by Spatie](https://github.com/spatie/laravel-package-tools)

## 🎭 License

The MIT License (MIT). Please have a look at the license file in each package for more information.
