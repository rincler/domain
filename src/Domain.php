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
     * @var TldRetrievalMode|null
     */
    private static $defaultTldRetrievalMode = null;

    /**
     * @var callable|null
     */
    private static $customTldsExtractor = null;

    /**
     * @var Domain[]|null
     */
    private static $customTlds = null;

    public static function isValid(string $domain): bool
    {
        try {
            new static($domain);

            return true;
        } catch (InvalidDomainException $e) {
            return false;
        }
    }

    final public function __construct(string $domain)
    {
        $domain = trim($domain);

        if ($domain === '') {
            throw new InvalidDomainException(0, 'Empty domain');
        }

        $idn = \idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($idn === false) {
            throw new InvalidDomainException(
                $idnInfo['errors'],
                sprintf('Domain "%s" is not valid. Idn error bitset: %d', $domain, $idnInfo['errors'])
            );
        }

        $punycode = \idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($punycode === false) {
            throw new InvalidDomainException(
                $idnInfo['errors'],
                sprintf('Domain "%s" is not valid. Idn error bitset: %d', $domain, $idnInfo['errors'])
            );
        }

        $this->idn = $idn;
        $this->punycode = $punycode;

        $this->normalize();
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
        $labels = array_filter(explode('.', $this->asIdn()));

        return count($labels);
    }

    public function getZone(): ?self
    {
        if ($this->getLevel() === 1) {
            return null;
        }

        $dotPosition = mb_strpos($this->asIdn(), '.');
        $zone = mb_substr($this->asIdn(), $dotPosition + 1);

        return new static($zone);
    }

    public function getWithoutZone(): ?self
    {
        if ($this->getZone() === null) {
            return new static($this->asIdn());
        }

        $domainWithoutZone = preg_replace("/\.{$this->getZone()->asIdn()}$/u", '', $this->asIdn());

        return new static($domainWithoutZone);
    }

    public function getTld(?TldRetrievalMode $mode = null): ?self
    {
        $mode = $mode ?? static::getDefaultTldRetrievalMode();

        if ($this->getLevel() === 1) {
            return null;
        }

        if (!$mode->equals(TldRetrievalMode::TRUE())) {
            foreach (static::getCustomTlds() as $customTld) {
                if (preg_match("/\.$customTld$/u", $this->asIdn())) {
                    return $customTld;
                }
            }
        }

        if ($mode->equals(TldRetrievalMode::CUSTOM())) {
            return null;
        }

        $dotPosition = mb_strrpos($this->asIdn(), '.');
        $tld = mb_substr($this->asIdn(), $dotPosition + 1);

        return new static($tld);
    }

    public function getWithoutTld(?TldRetrievalMode $mode = null): ?self
    {
        $mode = $mode ?? static::getDefaultTldRetrievalMode();

        if ($this->getTld($mode) === null) {
            return new static($this->asIdn());
        }

        $domainWithoutTld = preg_replace("/\.{$this->getTld($mode)->asIdn()}$/u", '', $this->asIdn());

        return new static($domainWithoutTld);
    }

    public function equals(self $domain): bool
    {
        return $this->asIdn() === $domain->asIdn();
    }

    public function __toString(): string
    {
        return $this->idn;
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
     * @return Domain[]
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

        usort(self::$customTlds, function (Domain $tldA, Domain $tldB) {
            return $tldB->getLevel() <=> $tldA->getLevel();
        });

        return self::$customTlds;
    }

    private function normalize(): void
    {
        $this->removeDotOnEnd();
        $this->toLowerCase();
    }

    private function removeDotOnEnd(): void
    {
        $lastCharacter = mb_substr($this->asIdn(), -1);
        if ($lastCharacter === '.') {
            $this->idn = mb_substr($this->asIdn(), 0, -1);
            $this->punycode = mb_substr($this->asPunycode(), 0, -1);
        }
    }

    private function toLowerCase(): void
    {
        $this->idn = mb_strtolower($this->asIdn());
        $this->punycode = mb_strtolower($this->asPunycode());
    }
}
