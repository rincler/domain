# Domain

[![Packagist Version](https://img.shields.io/packagist/v/rincler/domain)](https://packagist.org/packages/rincler/domain)
![PHP Version](https://img.shields.io/packagist/php-v/rincler/domain)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/rincler/domain/ci.yml?branch=master)

A domain name value object that supports IDN and Punycode, FQDNs, validates input, and provides easy access to the domain level, labels, zone, TLD, eTLD and eTLD+1.

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
echo $domain->getAbsolute(); // sub.example.com.

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

$domain = new Domain('sub.example.com.');
var_dump($domain->isFqdn()) // true
echo $domain // sub.example.com.
echo $domain->getZone() // example.com.
echo $domain->getRelative(); // sub.example.com
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
- `getTld(): Domain` - Returns the domain’s top-level domain (TLD).
- `getWithoutTld(): Domain` - Returns the domain excluding the TLD.
- `getETld(): Domain` - Returns the domain’s effective top-level domain (eTLD).
- `getWithoutETld(): Domain` - Returns the domain excluding the eTLD.
- `getETldPlusOne(): Domain` - Returns the domain’s effective top-level domain plus the next part (eTLD+1).
- `getWithoutETldPlusOne(): Domain` - Returns the domain excluding the eTLD+1.
- `getLabels(): array` - Returns an array containing all labels of the domain.
- `getLabel(int $level): array` - Returns the label corresponding to the given domain level.
- `sliceToLevel(int $level): array` - Returns an array of domain labels up to the given level.
- `getAbsolute(): Domain` - Returns an absolute domain with a dot at the end (FQDN).
- `isAbsolute(): bool` - Returns `true` if the domain is absolute (FQDN with trailing dot), `false` otherwise.
- `getFqdn(): Domain` - Alias for `getAbsolute()`.
- `isFqdn(): bool` - Alias for `isAbsolute()`.
- `getRelative(): Domain` - Returns an relative domain.
- `isRoot(): bool` - Returns `true` if the domain is root, `false` otherwise.
- `getClone(): Domain` - Returns a copy of the domain.
- `__toString(): string` - Magic method returning the domain equivalent to `asIdn()`.

### eTLD

See [eTLD](https://developer.mozilla.org/en-US/docs/Glossary/eTLD) and [Public Suffix List](https://publicsuffix.org/).

To use eTLDs, you must specify an eTLD provider via the `setETldProvider` method:

```php
<?php

use \Rincler\Domain\Domain;

Domain::setETldProvider(static function () {
    return ['net.ru', 'org.ru'];
});

$domain = new Domain('sub.example.net.ru');

echo $domain->getETld(); // net.ru
echo $domain->getETldPlusOne(); // example.net.ru
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
