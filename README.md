# Domain

[![Packagist Version](https://img.shields.io/packagist/v/rincler/domain)](https://packagist.org/packages/rincler/domain)
![PHP Version](https://img.shields.io/packagist/php-v/rincler/domain)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/rincler/domain/ci.yml?branch=master)

A domain name value object. Works with IDN and punycode syntax, validates and easily retrieves the level, zone and TLD (top-level domain).

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

- static `isValid(): bool` - Returns `true` if the domain is valid, returns `false` otherwise
- `__constructor(string $domain)` - The constructor validates the domain (throws `InvalidDomainException` if the domain is not valid) and creates the value object
- `asIdn(): string` - Returns the domain in IDN syntax
- `asPunycode(): string` - Returns the domain in Punycode syntax
- `equals(Domain $domain): bool` - Returns `true` if the current domain equals the specified domain, returns `false` otherwise
- `getLevel(): int` - Returns the number of domain levels
- `getZone(): Domain` - Returns the domain zone
- `getWithoutZone(): Domain` - Returns the domain without a zone
- `getTld(?TldRetrievalMode $mode = null): Domain` - Returns the top-level domain
- `getWithoutTld(?TldRetrievalMode $mode = null): Domain` - Returns the domain without a top-level domain
- `getLabels(): array` - Returns an array of the domain labels
- `getLabel(int $level): array` - Returns the label of the domain with number $level
- `sliceToLevel(int $level): array` - Returns the domain to level with number $level
- `__toString(): string` - An equivalent for the `asIdn` method

### Custom TLDs

You can use custom TLDs, e.g.: `net.ru` or `org.ru`, using the `setCustomTlds` method:

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

- `TldRetrievalMode::TRUE()` - the mode to retrieving only true TLD
- `TldRetrievalMode::CUSTOM()` - the mode to retrieving only custom TLD
- `TldRetrievalMode::MIX()` - the mode to retrieving custom TLD and true TLD if custom is not found (this mode is used by default)

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

Also, you can specify the TLD retriever mode globally:

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
// You can still pass the mode as argument:
echo $domain->getTld(TldRetrievalMode::CUSTOM()); // net.ru
```

## Why PHP >= 7.3?

Validation of domain in intl extension fixed in 7.3.0. See <http://bugs.php.net/76829>

## License

This library is released under the [MIT license](./LICENSE).
