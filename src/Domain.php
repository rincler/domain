<?php

declare(strict_types=1);

namespace Rincler\Domain;

class Domain
{
    /**
     * @var string
     */
    private $IDN;

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

    private const INVALID_DOMAIN_CHARACTERS = -101;

    public static function isValid(string $domainRaw): bool
    {
        try {
            new static($domainRaw);

            return true;
        } catch (InvalidDomainException $e) {
            return false;
        }
    }

    public function __construct(string $domainRaw)
    {
        if (mb_substr($domainRaw, -1) === '.' && mb_substr($domainRaw, -2) !== '..') {
            $this->isAbsolute = true;
            $relativeDomainRaw = mb_substr($domainRaw, 0, -1);
        } else {
            $relativeDomainRaw = $domainRaw;
        }

        if ($relativeDomainRaw === '') {
            $this->IDN = '';
            $this->punycode = '';
            return;
        }

        $idn = \idn_to_utf8($relativeDomainRaw, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($idn === false) {
            throw new InvalidDomainException(
                sprintf('Domain "%s" is not valid. IDN error bitset: %d', $relativeDomainRaw, $idnInfo['errors']),
                $idnInfo['errors'],
            );
        }

        $punycode = \idn_to_ascii($relativeDomainRaw, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $idnInfo);

        if ($punycode === false) {
            throw new InvalidDomainException(
                sprintf('Domain "%s" is not valid. IDN error bitset: %d.', $relativeDomainRaw, $idnInfo['errors']),
                $idnInfo['errors'],
            );
        }

        if (!\filter_var($punycode, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidDomainException(
                sprintf('Domain "%s" is not valid. Invalid characters.', $domainRaw),
                self::INVALID_DOMAIN_CHARACTERS,
            );
        }

        $this->IDN = $idn;
        $this->punycode = $punycode;
    }

    public function asIDN(): string
    {
        return $this->preservingAbsoluteness($this->IDN);
    }

    public function asPunycode(): string
    {
        return $this->preservingAbsoluteness($this->punycode);
    }

    public function equals(self $domain): bool
    {
        return $this->relative()->asIDN() === $domain->relative()->asIDN();
    }

    public function level(): int
    {
        return count($this->labels());
    }

    public function zone(): self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Root domain has no zone.');
        }

        if ($this->level() === 1) {
            return new static('');
        }

        $dotPosition = mb_strpos($this->asIDN(), '.');
        $zoneRaw = mb_substr($this->asIDN(), $dotPosition + 1);

        return new static($zoneRaw);
    }

    public function TLD(): self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Root domain has no TLD.');
        }

        $domain = $this->relative();

        if ($domain->level() === 1) {
            return $domain;
        }

        $dotPosition = mb_strrpos($domain->asIDN(), '.');
        $tldRaw = mb_substr($domain->asIDN(), $dotPosition + 1);

        return new static($tldRaw);
    }

    public function eTLD(): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Root domain has no eTLD.');
        }

        $domain = $this->relative();

        foreach (static::eTLDs() as $eTLDItem) {
            if ($domain->equals($eTLDItem) || preg_match("/\.$eTLDItem$/u", $domain->asIDN())) {
                return $eTLDItem;
            }
        }

        return null;
    }

    public function eTLDPlusOne(): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Root domain has no eTLD+1.');
        }

        $domain = $this->relative();

        $eTLD = null;

        foreach (static::eTLDs() as $eTLDItem) {
            if ($domain->equals($eTLDItem) || preg_match("/\.$eTLDItem$/u", $domain->asIDN())) {
                $eTLD = $eTLDItem;
                break;
            }
        }

        if ($eTLD === null || $domain->level() < $eTLD->level() + 1) {
            return null;
        }

        return $domain->sliceToLevel($eTLD->level() + 1);
    }

    public function without(?self $suffix): ?self
    {
        if ($this->isRoot()) {
            throw new RootDomainException('Root domain has no suffix.');
        }

        if ($suffix === null || $suffix->isRoot() || $suffix->level() > $this->level()) {
            return $this->clone()->relative();
        }

        if ($this->equals($suffix)) {
            return null;
        }

        $domainWithoutSuffixRaw = preg_replace("/\.{$suffix->relative()->asIDN()}$/u", '', $this->relative()->asIDN());

        return new static($domainWithoutSuffixRaw);
    }

    /**
     * @return static[]
     */
    public function labels(): array
    {
        if ($this->isRoot()) {
            return [];
        }

        $domain = $this->relative();

        $labelsRaw = array_reverse(explode('.', $domain->asIDN()));

        return array_map(static function (string $labelRaw) {
            return new static($labelRaw);
        }, $labelsRaw);
    }

    public function label(int $level): self
    {
        $labels = $this->labels();
        $idx = $level - 1;

        if (!isset($labels[$idx])) {
            throw new \OutOfRangeException(sprintf('A label on the level %d does not exist.', $level));
        }

        return $labels[$idx];
    }

    public function sliceToLevel(int $level): self
    {
        $labels = $this->labels();

        if (!isset($labels[$level - 1])) {
            throw new \OutOfRangeException(sprintf('A label on the level %d does not exist.', $level));
        }

        $slice = array_slice($labels, 0, $level);

        return new static(implode('.', array_reverse($slice)));
    }

    public function absolute(): self
    {
        return new static($this->IDN . '.');
    }

    public function isAbsolute(): bool
    {
        return $this->isAbsolute;
    }

    public function FQDN(): self
    {
        return $this->absolute();
    }

    public function isFQDN(): bool
    {
        return $this->isAbsolute();
    }

    public function relative(): self
    {
        return new static($this->IDN);
    }

    public function isRoot(): bool
    {
        return $this->IDN === '';
    }

    public function clone(): self
    {
        return new static($this->asIDN());
    }

    public function __toString(): string
    {
        return $this->asIDN();
    }

    /**
     * @param callable $provider Callback, that returns an array of eTLDs, e.g. `[net.ru, org.ru]`.
     *                           The callback is invoked once (on `eTLD` and `eTLDPlusOne`), and its result is cached.
     */
    public static function eTLDSetProvider(callable $provider): void
    {
        self::$eTLDProvider = $provider;
        self::$eTLDs = null;
    }

    public static function eTLDUnset(): void
    {
        self::$eTLDProvider = null;
        self::$eTLDs = null;
    }

    /**
     * @return static[]
     */
    protected static function eTLDs(): array
    {
        if (self::$eTLDProvider === null) {
            return [];
        }

        if (self::$eTLDs !== null) {
            return self::$eTLDs;
        }

        $eTLDsRaw = call_user_func(self::$eTLDProvider);

        self::$eTLDs = array_map(static function (string $eTLDRaw) {
            return new static($eTLDRaw);
        }, $eTLDsRaw);

        usort(self::$eTLDs, function (self $tldA, self $tldB) {
            return $tldB->level() <=> $tldA->level();
        });

        return self::$eTLDs;
    }

    protected function preservingAbsoluteness(string $domainRaw): string
    {
        return $this->isAbsolute() ? $domainRaw . '.' : $domainRaw;
    }
}
