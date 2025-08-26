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

    /**
     * @var bool
     */
    private $isAbsolute = false;

    /**
     * @var callable|null
     */
    private static $eTLDProvider = null;

    /**
     * @var static[]|null
    */
    private static $eTLDs = null;

    public static function isValid(string $domainRaw): bool
    {
        try {
            new static($domainRaw);

            return true;
        } catch (InvalidDomainException $e) {
            return false;
        }
    }

    final public function __construct(string $domainRaw)
    {
        if (mb_substr($domainRaw, -1) === '.' && mb_substr($domainRaw, -2) !== '..') {
            $this->isAbsolute = true;
            $relativeDomainRaw = mb_substr($domainRaw, 0, -1);
        } else {
            $relativeDomainRaw = $domainRaw;
        }

        if ($relativeDomainRaw === '') {
            $this->idn = '';
            $this->punycode = '';
            return;
        }

        $idn = \idn_to_utf8($relativeDomainRaw, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($idn === false) {
            throw new InvalidDomainException(
                $idnInfo['errors'],
                sprintf('Domain "%s" is not valid. Idn error bitset: %d', $relativeDomainRaw, $idnInfo['errors'])
            );
        }

        $punycode = \idn_to_ascii($relativeDomainRaw, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($punycode === false) {
            throw new InvalidDomainException(
                $idnInfo['errors'],
                sprintf('Domain "%s" is not valid. Idn error bitset: %d.', $relativeDomainRaw, $idnInfo['errors'])
            );
        }

        if (!\filter_var($punycode, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidDomainException(
                0,
                sprintf('Domain "%s" is not valid. Invalid characters.', $domainRaw)
            );
        }

        $this->idn = $idn;
        $this->punycode = $punycode;
    }

    public function asIdn(): string
    {
        return $this->preservingAbsoluteness($this->idn);
    }

    public function asPunycode(): string
    {
        return $this->preservingAbsoluteness($this->punycode);
    }

    public function equals(self $domain): bool
    {
        return $this->getRelative()->asIdn() === $domain->getRelative()->asIdn();
    }

    public function getLevel(): int
    {
        return count($this->getLabels());
    }

    public function getZone(): self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine zone for the root domain.');
        }

        if ($this->getLevel() === 1) {
            return new Domain('');
        }

        $dotPosition = mb_strpos($this->asIdn(), '.');
        $zoneRaw = mb_substr($this->asIdn(), $dotPosition + 1);

        return new static($zoneRaw);
    }

    public function getWithoutZone(): self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine zone for the root domain.');
        }

        if ($this->getLevel() === 1) {
            return $this->getRelative();
        }

        $domainWithoutZoneRaw = preg_replace("/\.{$this->getZone()->asIdn()}$/u", '', $this->asIdn());

        return new static($domainWithoutZoneRaw);
    }

    public function getTld(): self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine TLD for the root domain.');
        }

        $domain = $this->getRelative();

        if ($domain->getLevel() === 1) {
            return $domain;
        }

        $dotPosition = mb_strrpos($domain->asIdn(), '.');
        $tldRaw = mb_substr($domain->asIdn(), $dotPosition + 1);

        return new static($tldRaw);
    }

    public function getWithoutTld(): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine TLD for the root domain.');
        }

        $domain = $this->getRelative();
        $tld = $domain->getTld();

        if ($domain->equals($tld)) {
            return null;
        }

        $domainWithoutTldRaw = preg_replace("/\.{$tld->asIdn()}$/u", '', $domain->asIdn());

        return new static($domainWithoutTldRaw);
    }

    public function getETld(): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine eTLD for the root domain.');
        }

        $domain = $this->getRelative();

        foreach (static::getETlds() as $eTldItem) {
            if ($domain->equals($eTldItem) || preg_match("/\.$eTldItem$/u", $domain->asIdn())) {
                return $eTldItem;
            }
        }

        return null;
    }

    public function getWithoutETld(): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine eTLD for the root domain.');
        }

        $domain = $this->getRelative();
        $eTld = $domain->getETld();

        if ($eTld === null) {
            return $domain;
        }

        if ($domain->equals($eTld)) {
            return null;
        }

        $domainWithoutETldRaw = preg_replace("/\.{$eTld->asIdn()}$/u", '', $domain->asIdn());

        return new static($domainWithoutETldRaw);
    }

    public function getETldPlusOne(): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine eTLD+1 for the root domain.');
        }

        $domain = $this->getRelative();

        $eTld = null;

        foreach (static::getETlds() as $eTldItem) {
            if ($domain->equals($eTldItem) || preg_match("/\.$eTldItem$/u", $domain->asIdn())) {
                $eTld = $eTldItem;
                break;
            }
        }

        if ($eTld === null || $domain->getLevel() < $eTld->getLevel() + 1) {
            return null;
        }

        return $domain->sliceToLevel($eTld->getLevel() + 1);
    }

    public function getWithoutEtldPlusOne(): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine eTLD+1 for the root domain.');
        }

        $domain = $this->getRelative();
        $eTldPlusOne = $domain->getETldPlusOne();

        if ($eTldPlusOne === null) {
            return $domain;
        }

        if ($domain->equals($eTldPlusOne)) {
            return null;
        }

        $domainWithoutETldPlusOneRaw = preg_replace("/\.{$eTldPlusOne->asIdn()}$/u", '', $domain->asIdn());

        return new static($domainWithoutETldPlusOneRaw);
    }

    /**
     * @return static[]
     */
    public function getLabels(): array
    {
        $domain = $this->getRelative();

        $labelsRaw = array_reverse(explode('.', $domain->asIdn()));

        return array_map(static function (string $labelRaw) {
            return new static($labelRaw);
        }, $labelsRaw);
    }

    public function getLabel(int $level): self
    {
        $labels = $this->getLabels();
        $idx = $level - 1;

        if (!isset($labels[$idx])) {
            throw new \OutOfRangeException(sprintf('A label on the level %d does not exist.', $level));
        }

        return $labels[$idx];
    }

    public function sliceToLevel(int $level): self
    {
        $labels = $this->getLabels();

        if (!isset($labels[$level - 1])) {
            throw new \OutOfRangeException(sprintf('A label on the level %d does not exist.', $level));
        }

        $slice = array_slice($labels, 0, $level);

        return new static(implode('.', array_reverse($slice)));
    }

    public function getAbsolute(): self
    {
        return new static($this->idn . '.');
    }

    public function isAbsolute(): bool
    {
        return $this->isAbsolute;
    }

    public function getFqdn(): self
    {
        return $this->getAbsolute();
    }

    public function isFqdn(): bool
    {
        return $this->isAbsolute();
    }

    public function getRelative(): self
    {
        return new static($this->idn);
    }

    public function isRoot(): bool
    {
        return $this->idn === '';
    }

    public function getClone(): self
    {
        return new static($this->asIdn());
    }

    public function __toString(): string
    {
        return $this->asIdn();
    }

    /**
     * @param callable $provider Callback, that returns an array of eTLDs, e.g. `[net.ru, org.ru]`.
     *                            The callback is invoked once (on `getETld`, `getWithoutETld`, `getETldPlusOne` and `getWithoutETldPlusOne`), and its result is cached.
     */
    public static function setETldProvider(callable $provider): void
    {
        self::$eTLDProvider = $provider;
        self::$eTLDs = null;
    }

    public static function unsetETlds(): void
    {
        self::$eTLDProvider = null;
        self::$eTLDs = null;
    }

    /**
     * @return static[]
     */
    protected static function getETlds(): array
    {
        if (self::$eTLDProvider === null) {
            return [];
        }

        if (self::$eTLDs !== null) {
            return self::$eTLDs;
        }

        $eTLDsRaw = call_user_func(self::$eTLDProvider);

        self::$eTLDs = array_map(static function (string $eTldRaw) {
            return new static($eTldRaw);
        }, $eTLDsRaw);

        usort(self::$eTLDs, function (self $tldA, self $tldB) {
            return $tldB->getLevel() <=> $tldA->getLevel();
        });

        return self::$eTLDs;
    }

    protected function preservingAbsoluteness(string $domainRaw): string
    {
        return $this->isAbsolute() ? $domainRaw . '.' : $domainRaw;
    }
}
