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
     * @var TldRetrievalMode|null
     */
    private static $defaultTldRetrievalMode = null;

    /**
     * @var callable|null
     */
    private static $customTldsExtractor = null;

    /**
     * @var static[]|null
     */
    private static $customTlds = null;

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

    public function getTld(?TldRetrievalMode $mode = null): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine TLD for the root domain.');
        }

        $domain = $this->getRelative();

        $mode = $mode ?? static::getDefaultTldRetrievalMode();

        if (!$mode->equals(TldRetrievalMode::TRUE())) {
            foreach (static::getCustomTlds() as $customTld) {
                if ($domain->equals($customTld) || preg_match("/\.$customTld$/u", $domain->asIdn())) {
                    return $customTld;
                }
            }
        }

        if ($mode->equals(TldRetrievalMode::CUSTOM())) {
            return null;
        }

        if ($domain->getLevel() === 1) {
            return $domain;
        }

        $dotPosition = mb_strrpos($domain->asIdn(), '.');
        $tldRaw = mb_substr($domain->asIdn(), $dotPosition + 1);

        return new static($tldRaw);
    }

    public function getWithoutTld(?TldRetrievalMode $mode = null): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Cannot determine TLD for the root domain.');
        }

        $domain = $this->getRelative();
        $tld = $domain->getTld($mode);

        if ($tld === null) {
            return $domain;
        }

        $mode = $mode ?? static::getDefaultTldRetrievalMode();

        if ($domain->equals($tld)) {
            return null;
        }

        $domainWithoutTldRaw = preg_replace("/\.{$domain->getTld($mode)->asIdn()}$/u", '', $domain->asIdn());

        return new static($domainWithoutTldRaw);
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

    protected function preservingAbsoluteness(string $domainRaw): string
    {
        return $this->isAbsolute() ? $domainRaw . '.' : $domainRaw;
    }

    public static function setDefaultTldRetrievalMode(TldRetrievalMode $mode): void
    {
        self::$defaultTldRetrievalMode = $mode;
    }

    protected static function getDefaultTldRetrievalMode(): TldRetrievalMode
    {
        return self::$defaultTldRetrievalMode ?? TldRetrievalMode::MIX();
    }

    /**
     * Defines custom TLDs.
     *
     * @param callable $extractor Callback, that returns an array of custom TLDs, e.g. `[net.ru, org.ru]`.
     *                            The callback is called (on `getTld` and `getWithoutTld`) once and returned array is cached.
     */
    public static function setCustomTlds(callable $extractor): void
    {
        self::$customTldsExtractor = $extractor;
        self::$customTlds = null;
    }

    public static function unsetCustomTlds(): void
    {
        self::$customTldsExtractor = null;
        self::$customTlds = null;
    }

    /**
     * @return static[]
     */
    protected static function getCustomTlds(): array
    {
        if (self::$customTldsExtractor === null) {
            return [];
        }

        if (self::$customTlds !== null) {
            return self::$customTlds;
        }

        $customTlds = call_user_func(self::$customTldsExtractor);

        self::$customTlds = array_map(static function (string $customTld) {
            return new static($customTld);
        }, $customTlds);

        usort(self::$customTlds, function (self $tldA, self $tldB) {
            return $tldB->getLevel() <=> $tldA->getLevel();
        });

        return self::$customTlds;
    }
}
