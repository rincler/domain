# Domain

[![Packagist Version](https://img.shields.io/packagist/v/rincler/domain)](https://packagist.org/packages/rincler/domain)
[![Build Status](https://travis-ci.com/rincler/domain.svg?branch=master)](https://travis-ci.com/rincler/domain)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/rincler/domain/1.0.0)

Domain name value object

## Usage

```php
<?php

use \Rincler\Domain\Domain;

$domain = new Domain('sub.domain.com');
echo $domain; // sub.domain.com
echo $domain->getIdn(); // sub.domain.com
echo $domain->getPunycode(); // sub.domain.com
echo $domain->getZone(); // domain.com
echo $domain->getWithoutZone(); // sub
echo $domain->getTld(); // com
echo $domain->getWithoutTld(); // sub.domain
echo $domain->getLevel(); // 3

$domain = new Domain('домен.рф');
echo $domain->getIdn(); // домен.рф
echo $domain->getPunycode(); // xn--d1acufc.xn--p1ai

$domain = new Domain('xn--d1acufc.xn--p1ai');
echo $domain->getIdn(); // домен.рф
echo $domain->getPunycode(); // xn--d1acufc.xnn
```
    
## Installation

    composer require rincler/domain
    
## Documentation

- static `isValid(): bool` - Returns `true` if domain is valid, returns `false` otherwise
- `__constructor` - The constructor validates domain (throws exception if domain is not valid or domain ends with dot) and creates object of domain
- `getIdn(): string` - Returns domain as IDN string
- `getPunycode(): string` - Returns domain as Punycode string
- `getLevel(): int` - Returns domain levels count
- `getZone(): Domain` - Returns zone of domain
- `getWithoutZone(): Domain` - Returns domain without zone
- `getTld(): Domain` - Returns top-level domain
- `getWithoutTld(): Domain` - Returns domain without top-level domain
- `equals(Domain $domain): bool` - Returns `true` if current domain equals checked domain, returns `false` otherwise
- `__toString(): string` - Analog for `getIdn`

## Why PHP >= 7.3?

Validation of domain in intl extension fixed in 7.3.0. See http://bugs.php.net/76829

## License

This library is released under the [MIT license](./LICENSE).
