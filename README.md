# Domain

[![Packagist Version](https://img.shields.io/packagist/v/rincler/domain)](https://packagist.org/packages/rincler/domain)
![PHP Version](https://img.shields.io/packagist/php-v/rincler/domain)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/rincler/domain/ci.yml?branch=master)

A domain name value object that supports IDN and Punycode, FQDN, validates input, and provides easy access to the domain level, labels, zone, TLD, eTLD and eTLD+1.

## Usage

```php
<?php

use \Rincler\Domain\Domain;

$domain = new Domain('sub.example.com');
echo $domain; // sub.example.com
echo $domain->asIDN(); // sub.example.com
echo $domain->asPunycode(); // sub.example.com
echo $domain->zone(); // example.com
echo $domain->without($domain->zone()); // sub
echo $domain->TLD(); // com
echo $domain->without($domain->TLD()); // sub.example
echo $domain->level(); // 3
echo $domain->labels(); // [new Domain('com'), new Domain('example'), new Domain('sub')]
echo $domain->label(2); // example
echo $domain->sliceToLevel(2); // example.com
echo $domain->absolute(); // sub.example.com.

$domain = new Domain('пример.рф');
echo $domain->asIDN(); // пример.рф
echo $domain->asPunycode(); // xn--e1afmkfd.xn--p1ai

$domain = new Domain('xn--e1afmkfd.xn--p1ai');
echo $domain->asIDN(); // пример.рф
echo $domain->asPunycode(); // xn--e1afmkfd.xn--p1ai

var_dump($domain->equals(new Domain('xn--e1afmkfd.xn--p1ai'))) // true
var_dump($domain->equals(new Domain('пример.рф'))) // true
var_dump($domain->equals(new Domain('суб.пример.рф'))) // false

var_dump(Domain::isValid('example.com')) // true
var_dump(Domain::isValid('пример.рф')) // true
var_dump(Domain::isValid('exam_ple.com')) // false
var_dump(Domain::isValid('.example.com')) // false

$domain = new Domain('sub.example.com.');
var_dump($domain->isFQDN()) // true
echo $domain // sub.example.com.
echo $domain->zone() // example.com.
echo $domain->relative(); // sub.example.com
```

## Installation

```bash
composer require rincler/domain
```

## Documentation

- static `isValid(): bool` - Returns `true` if the domain is valid, `false` otherwise.
- `__constructor(string $domain)` - The constructor validates the domain and throws `InvalidDomainException` if it is not valid, then creates the value object.
- `asIDN(): string` - Returns the domain in IDN format.
- `asPunycode(): string` - Returns the domain in Punycode format.
- `equals(Domain $domain): bool` - Returns `true` if the current domain equals the specified domain, `false` otherwise.
- `level(): int` - Returns the number of levels in the domain.
- `zone(): Domain` - Returns the domain zone.
- `TLD(): Domain` - Returns the domain’s top-level domain (TLD).
- `eTLD(): ?Domain` - Returns the domain’s effective top-level domain (eTLD).
- `eTLDPlusOne(): ?Domain` - Returns the domain’s effective top-level domain plus the next label (eTLD+1).
- `without(Domain $suffix): ?Domain` - Returns the domain without the specified suffix.
- `labels(): Domain[]` - Returns an array containing all labels of the domain.
- `label(int $level): Domain` - Returns the label corresponding to the given domain level.
- `sliceToLevel(int $level): Domain` - Returns the domain up to the specified level.
- `absolute(): Domain` - Returns the absolute form of the domain (FQDN) with a trailing dot.
- `isAbsolute(): bool` - Returns `true` if the domain is absolute (FQDN), `false` otherwise.
- `FQDN(): Domain` - Alias for `absolute()`.
- `isFQDN(): bool` - Alias for `isAbsolute()`.
- `relative(): Domain` - Returns the relative form of the domain.
- `isRoot(): bool` - Returns `true` if the domain is root, `false` otherwise.
- `clone(): Domain` - Returns a copy of the domain.
- `__toString(): string` - Magic method returning the domain equivalent to `asIDN()`.

### URL Domain

Domains used in URLs cannot be absolute (with a dot at the end) and do not have a root zone.
In such cases, you can use the `UrlDomain` class instead of `Domain`.

### eTLD

See [eTLD](https://developer.mozilla.org/en-US/docs/Glossary/eTLD) and [Public Suffix List](https://publicsuffix.org/).

To use eTLDs, you must specify an eTLD provider via the `eTLDSetProvider` method:

```php
<?php

use \Rincler\Domain\Domain;

Domain::eTLDSetProvider(static function () {
    return ['net.ru', 'org.ru'];
});

$domain = new Domain('sub.example.net.ru');

echo $domain->eTLD(); // net.ru
echo $domain->eTLDPlusOne(); // example.net.ru
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
