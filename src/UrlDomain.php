<?php

declare(strict_types=1);

namespace Rincler\Domain;

class UrlDomain extends Domain
{
    private const INVALID_DOMAIN_ROOT = -201;
    private const INVALID_DOMAIN_ABSOLUTE = -202;

    public function __construct(string $domainRaw)
    {
        parent::__construct($domainRaw);

        if ($this->isRoot()) {
            throw new InvalidDomainException(
                sprintf('Domain "%s" is not valid. A URL domain must not be root.', $domainRaw),
                self::INVALID_DOMAIN_ROOT,
            );
        }

        if ($this->isAbsolute()) {
            throw new InvalidDomainException(
                sprintf('Domain "%s" is not valid. A URL domain must not be absolute.', $domainRaw),
                self::INVALID_DOMAIN_ABSOLUTE,
            );
        }
    }

    public function zone(): Domain
    {
        if ($this->level() === 1) {
            throw new UrlDomainException('A URL top-level domain has no zone.');
        }

        return parent::zone();
    }
}
