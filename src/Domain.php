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
     * @var static[]|null
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
            throw new InvalidDomainException(0, 'Empty domain.');
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
                sprintf('Domain "%s" is not valid. Idn error bitset: %d.', $domain, $idnInfo['errors'])
            );
        }

        if (!\filter_var($punycode, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidDomainException(
                0,
                sprintf('Domain "%s" is not valid. Invalid characters.', $domain)
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

    public function equals(self $domain): bool
    {
        return $this->asIdn() === $domain->asIdn();
    }

    public function getLevel(): int
    {
        return count($this->getLabels());
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

    /**
     * @return static[]
     */
    public function getLabels(): array
    {
        $labels = array_reverse(explode('.', $this->asIdn()));

        return array_map(static function (string $label) {
            return new static($label);
        }, $labels);
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

    private function normalize(): void
    {
        $this->removeDotAtTheEnd();
        $this->toLowerCase();
    }

    private function removeDotAtTheEnd(): void
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
