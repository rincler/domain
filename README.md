# Domain
Domain name value object

## Usage

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
    
## Installation

    composer require rincler/domain
    
## Documentation

- static `isValid` - Returns `true` if domain is valid, returns `false` otherwise (`bool`)
- `__constructor` - The constructor validates domain (throws exception if domain is not valid or domain ends with dot) and creates object of domain
- `getIdn` - Returns domain as IDN string (`string`)
- `getPunycode` - Returns domain as Punycode string (`string`)
- `getLevel` - Returns domain levels count (`int`)
- `getZone` - Returns zone of domain (`\Rincler\Domain\Domain`)
- `getWithoutZone` - Returns domain without zone (`\Rincler\Domain\Domain`)
- `getTld` - Returns top-level domain (`\Rincler\Domain\Domain`)
- `getWithoutTld` - Returns domain without top-level domain (`\Rincler\Domain\Domain`)
- `equals` - Returns `true` if current domain equals checked domain, returns `false` otherwise (`bool`)
- `__toString` - Analog for `getIdn`
