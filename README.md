# Domain

[![Packagist Version](https://img.shields.io/packagist/v/rincler/domain)](https://packagist.org/packages/rincler/domain)
![PHP Version](https://img.shields.io/packagist/php-v/rincler/domain)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/rincler/domain/ci.yml?branch=master)

A domain name value object that supports IDN and Punycode, validates input, and provides easy access to the domain level, zone and TLD.

## Usage

```php
<?php

use \Rincler\Domain\Domain;

$domain = new Domain('sub.example.com');
echo $domain; // sub.example.com
echo $domain->asIdn(); // sub.example.com
echo $domain->asPunycode(); // sub.example.com
echo $domain->getZone(); // example.com
echo $domain->getWithoutZone(); // sub
echo $domain->getTld(); // com
echo $domain->getWithoutTld(); // sub.example
echo $domain->getLevel(); // 3
echo $domain->getLabels(); // [new Domain('com'), new Domain('example'), new Domain('sub')]
echo $domain->getLabel(2); // example
echo $domain->sliceToLevel(2); // example.com

$domain = new Domain('пример.рф');
echo $domain->asIdn(); // пример.рф
echo $domain->asPunycode(); // xn--e1afmkfd.xn--p1ai

$domain = new Domain('xn--e1afmkfd.xn--p1ai');
echo $domain->asIdn(); // пример.рф
echo $domain->asPunycode(); // xn--e1afmkfd.xn--p1ai

var_dump($domain->equals(new Domain('xn--e1afmkfd.xn--p1ai'))) // true
var_dump($domain->equals(new Domain('пример.рф'))) // true
var_dump($domain->equals(new Domain('суб.пример.рф'))) // false

var_dump(Domain::isValid('example.com')) // true
var_dump(Domain::isValid('пример.рф')) // true
var_dump(Domain::isValid('exam_ple.com')) // false
var_dump(Domain::isValid('.example.com')) // false
```

## Installation

```bash
    composer require rincler/domain
```

## Documentation

- static `isValid(): bool` - Returns `true` if the domain is valid, `false` otherwise.
- `__constructor(string $domain)` - The constructor validates the domain and throws `InvalidDomainException` if it is not valid, then creates the value object.
- `asIdn(): string` - Returns the domain in IDN format.
- `asPunycode(): string` - Returns the domain in Punycode format.
- `equals(Domain $domain): bool` - Returns `true` if the current domain equals the specified domain, `false` otherwise.
- `getLevel(): int` - Returns the number of levels in the domain.
- `getZone(): Domain` - Returns the domain zone.
- `getWithoutZone(): Domain` - Returns the domain without its zone.
- `getTld(?TldRetrievalMode $mode = null): Domain` - Returns the domain’s top-level domain.
- `getWithoutTld(?TldRetrievalMode $mode = null): Domain` - Returns the domain excluding the top-level domain.
- `getLabels(): array` - Returns an array containing all labels of the domain.
- `getLabel(int $level): array` - Returns the label corresponding to the given domain level.
- `sliceToLevel(int $level): array` - Returns an array of domain labels up to the given level.
- `__toString(): string` - Magic method returning the domain equivalent to `asIdn()`.

### Custom TLDs

You can define custom TLDs, e.g., `net.ru` or `org.ru`, using the `setCustomTlds` method:

```php
<?php

use \Rincler\Domain\Domain;

$domain = new Domain('example.net.ru');

echo $domain->getTld(); // ru

Domain::setCustomTlds(static function () {
    return ['net.ru', 'org.ru'];
});

echo $domain->getTld(); // net.ru
```

You can specify the TLD retriever mode:

- `TldRetrievalMode::TRUE()` - Retrieves only true TLDs.
- `TldRetrievalMode::CUSTOM()` - Retrieves only custom TLDs.
- `TldRetrievalMode::MIX()` - Retrieves custom TLDs, and if a custom TLD is not found, falls back to true TLDs (this is the default mode).

```php
<?php

use \Rincler\Domain\Domain;
use \Rincler\Domain\TldRetrievalMode

$domain = new Domain('example.net.ru');

Domain::setCustomTlds(static function () {
    return ['net.ru', 'org.ru'];
});

echo $domain->getTld(TldRetrievalMode::TRUE()); // ru
echo $domain->getTld(TldRetrievalMode::CUSTOM()); // net.ru
echo $domain->getTld(TldRetrievalMode::MIX()); // net.ru

$domain = new Domain('example.ru');
echo $domain->getTld(TldRetrievalMode::TRUE()); // ru
echo $domain->getTld(TldRetrievalMode::CUSTOM()); // <null>
echo $domain->getTld(TldRetrievalMode::MIX()); // ru
```

You can also set the TLD retrieval mode globally:

```php
<?php

use \Rincler\Domain\Domain;
use \Rincler\Domain\TldRetrievalMode

$domain = new Domain('example.net.ru');

Domain::setDefaultTldRetrievalMode(TldRetrievalMode::TRUE());

Domain::setCustomTlds(static function () {
    return ['net.ru', 'org.ru'];
});

echo $domain->getTld(); // ru
// You can still pass the mode as an argument:
echo $domain->getTld(TldRetrievalMode::CUSTOM()); // net.ru
```

## Why PHP >= 7.3?

Domain validation in the intl extension was fixed in 7.3.0. See <http://bugs.php.net/76829>

## Development

Running tests in Docker:

1. Build the Docker image:

```bash
docker build -t php:8.4-cli-intl .
```

2. Run the tests:

```bash
docker run --rm -v $(pwd):/app/ php:8.4-cli-intl php vendor/bin/phpunit
```

## License

This library is released under the [MIT license](./LICENSE).
