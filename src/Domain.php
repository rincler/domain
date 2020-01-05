<?php

declare(strict_types=1);

namespace Rincler\Domain;

class Domain
{
    /**
     * @var string
     */
    private $idn;

    /**
     * @var string
     */
    private $punycode;

    public static function isValid(string $domain): bool
    {
        try {
            new Domain($domain);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function __construct(string $domain)
    {
        $this->idn = \idn_to_utf8($domain, 0, INTL_IDNA_VARIANT_UTS46);
        $this->punycode = \idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);

        if ($this->idn === false || $this->punycode === false) {
            throw new \Exception(sprintf('Domain "%s" is not valid.', $domain));
        }

        if ($this->getLastCharacter() === '.') {
            throw new \Exception(sprintf('Domain "%s" contains dot on end.', $domain));
        }

        $this->toLowerCase();
    }

    public function getIdn(): string
    {
        return $this->idn;
    }

    public function getPunycode(): string
    {
        return $this->punycode;
    }

    public function getLevel(): int
    {
        $labels = array_filter(explode('.', $this->idn));

        return count($labels);
    }

    public function getZone(): ?Domain
    {
        if ($this->getLevel() === 1) {
            return null;
        }

        $dotPosition = mb_strpos($this->idn, '.');
        $zone = mb_substr($this->idn, $dotPosition + 1);

        return new Domain($zone);
    }

    public function getWithoutZone(): ?Domain
    {
        if (!$this->getZone()) {
            return null;
        }

        $domainWithoutZone = str_replace('.'.$this->getZone()->getIdn(), '', $this->idn);

        return new Domain($domainWithoutZone);
    }

    public function getTld(): ?Domain
    {
        if ($this->getLevel() === 1) {
            return null;
        }

        $dotPosition = mb_strrpos($this->idn, '.');
        $tld = mb_substr($this->idn, $dotPosition + 1);

        return new Domain($tld);
    }

    public function getWithoutTld(): ?Domain
    {
        if (!$this->getTld()) {
            return null;
        }

        $domainWithoutTld = str_replace('.'.$this->getTld()->getIdn(), '', $this->idn);

        return new Domain($domainWithoutTld);
    }

    public function equals(Domain $domain): bool
    {
        return $this->getIdn() === $domain->getIdn();
    }

    public function __toString(): string
    {
        return $this->idn;
    }

    private function getLastCharacter(): string
    {
        return mb_substr($this->idn, -1);
    }

    private function toLowerCase(): void
    {
        $this->idn = mb_strtolower($this->idn);
        $this->punycode = mb_strtolower($this->punycode);
    }
}
