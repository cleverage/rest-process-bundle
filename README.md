CleverAge/RestProcessBundle
=======================

[![Test](https://github.com/cleverage/rest-process-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/cleverage/rest-process-bundle/actions/workflows/test.yml)
[![Packagist Version](https://img.shields.io/packagist/v/cleverage/rest-process-bundle)](https://packagist.org/packages/cleverage/rest-process-bundle)
[![License](https://img.shields.io/packagist/l/cleverage/rest-process-bundle)](LICENSE)

This bundle is a part of the [CleverAge/ProcessBundle](https://github.com/cleverage/process-bundle) project. 
It provides [Rest](https://en.wikipedia.org/wiki/REST) integration on Process bundle.

Compatible with [Symfony stable version and latest Long-Term Support (LTS) release](https://symfony.com/releases).

## Installation

```bash
composer require cleverage/rest-process-bundle
```

Without Symfony Flex, register the bundle in `config/bundles.php`:

```php
CleverAge\RestProcessBundle\CleverAgeRestProcessBundle::class => ['all' => true],
```

## Documentation

For usage documentation, see:
[docs/index.md](docs/index.md)

## Support & Contribution

For general support and questions, please use [Github](https://github.com/cleverage/rest-process-bundle/issues).
If you think you found a bug or you have a feature idea to propose, feel free to open an issue after looking at the [contributing](CONTRIBUTING.md) guide.

## License

This bundle is under the MIT license.
For the whole copyright, see the [LICENSE](LICENSE) file distributed with this source code.
