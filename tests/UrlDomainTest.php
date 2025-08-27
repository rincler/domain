<?php

declare(strict_types=1);

namespace Rincler\Tests;

use PHPUnit\Framework\TestCase;
use Rincler\Domain\InvalidDomainException;
use Rincler\Domain\UrlDomain;
use Rincler\Domain\UrlDomainException;

class UrlDomainTest extends TestCase
{
    public function testConstruct(): void
    {
        $domain = new UrlDomain('example.com');

        self::assertEquals('example.com', $domain);
    }

    /**
     * @dataProvider providerForInvalidDomain
     */
    public function testInvalidDomain(string $domain): void
    {
        $this->expectException(InvalidDomainException::class);
        new UrlDomain($domain);
    }

    public static function providerForInvalidDomain(): array
    {
        return [
            [''],
            ['.'],
            ['example.com.'],
        ];
    }

    /**
     * @dataProvider providerForIsValid
     */
    public function testIsValid(string $domain, bool $expectedIsValid): void
    {
        self::assertEquals($expectedIsValid, UrlDomain::isValid($domain));
    }

    public static function providerForIsValid(): array
    {
        return [
            ['example.com', true],
            ['', false],
            ['.', false],
            ['example.com.', false],
        ];
    }

    public function testGetZone(): void
    {
        $domain = new UrlDomain('sub.example.com');

        self::assertEquals('example.com', $domain->getZone());
    }

    public function testGetZoneOfTld(): void
    {
        $this->expectException(UrlDomainException::class);
        $domain = new UrlDomain('com');
        $domain->getZone();
    }
}
