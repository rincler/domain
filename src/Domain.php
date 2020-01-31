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
        } catch (InvalidDomainException $e) {
            return false;
        }
    }

    public function __construct(string $domain)
    {
        $this->idn = \idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($this->idn === false) {
            throw new InvalidDomainException($idnInfo['errors'], sprintf('Domain "%s" is not valid. Idn error bitset: %d', $domain, $idnInfo['errors']));
        }

        $this->punycode = \idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($this->punycode === false) {
            throw new InvalidDomainException($idnInfo['errors'], sprintf('Domain "%s" is not valid. Idn error bitset: %d', $domain, $idnInfo['errors']));
        }

        $this->removeDotOnEnd();
        $this->toLowerCase();
    }

    public function asIdn(): string
    {
        return $this->idn;
    }

    public function asPunycode(): string
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

        $domainWithoutZone = str_replace('.'.$this->getZone()->asIdn(), '', $this->idn);

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

        $domainWithoutTld = str_replace('.'.$this->getTld()->asIdn(), '', $this->idn);

        return new Domain($domainWithoutTld);
    }

    public function equals(Domain $domain): bool
    {
        return $this->asIdn() === $domain->asIdn();
    }

    public function __toString(): string
    {
        return $this->idn;
    }

    private function removeDotOnEnd(): void
    {
        $lastCharacter = mb_substr($this->idn, -1);
        if ($lastCharacter === '.') {
            $this->idn = mb_substr($this->idn, 0, -1);
            $this->punycode = mb_substr($this->punycode, 0, -1);
        }
    }

    private function toLowerCase(): void
    {
        $this->idn = mb_strtolower($this->idn);
        $this->punycode = mb_strtolower($this->punycode);
    }
}
