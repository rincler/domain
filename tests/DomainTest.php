<?php

declare(strict_types=1);

namespace Rincler\Tests;

use PHPUnit\Framework\TestCase;
use Rincler\Domain\Domain;
use Rincler\Domain\InvalidDomainException;
use Rincler\Domain\RootDomainException;
use Rincler\Domain\TldRetrievalMode;

class DomainTest extends TestCase
{
    /**
     * @dataProvider providerForConstruct
     */
    public function testConstruct(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, (string) $domain);
    }

    public static function providerForConstruct(): array
    {
        return [
            ['example.com', 'example.com'],
            ['example.com.', 'example.com.'],
            ['EXAMPLE.com', 'example.com'],
            ['12345.com', '12345.com'],
            ['exam-ple.com', 'exam-ple.com'],
            ['exam--ple.com', 'exam--ple.com'],
            ['', ''],
        ];
    }

    /**
     * @dataProvider providerForAsIdnAndAsPunycode
     */
    public function testAsIdn(string $domain, string $expectedIdn, string $expectedPunycode): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->asIdn());
    }

    /**
     * @dataProvider providerForAsIdnAndAsPunycode
     */
    public function testAsPunycode(string $domain, string $expectedIdn, string $expectedPunycode): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedPunycode, $domain->asPunycode());
    }

    public static function providerForAsIdnAndAsPunycode(): array
    {
        return [
            ['com', 'com', 'com'],
            ['рф', 'рф', 'xn--p1ai'],
            ['example.com', 'example.com', 'example.com'],
            ['пример.рф', 'пример.рф', 'xn--e1afmkfd.xn--p1ai'],
            // maximum length of domain label in punycode - 63
            [
                'aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63',
                'aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63',
                'aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63',
            ],
            [
                'ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа',
                'ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа',
                'xn--2550-43daaaacaaaacaaaagaaaahaaaasaaaamaaaaxaaaaraaaa2aaawaa',
            ],
            // maximum length of domain in punycode - 253
            [
                'aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbb61',
                'aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbb61',
                'aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbb61',
            ],
            [
                'ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааа',
                'ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааа',
                'xn--2550-43daaaacaaaacaaaagaaaahaaaasaaaamaaaaxaaaaraaaa2aaawaa.xn--2550-43daaaacaaaacaaaagaaaahaaaasaaaamaaaaxaaaaraaaa2aaawaa.xn--2550-43daaaacaaaacaaaagaaaahaaaasaaaamaaaaxaaaaraaaa2aaawaa.xn--2550-43daaaacaaaacaagaaaahaaaaqaaaamaaaavaaaaraaaa0aaawaa',
            ],
        ];
    }

    /**
     * @dataProvider providerForEqualsDomain
     */
    public function testEqualsDomain(string $domain1, string $domain2, bool $equal): void
    {
        $this->assertEquals($equal, (new Domain($domain1))->equals(new Domain($domain2)));
    }

    public function providerForEqualsDomain(): array
    {
        return [
            ['example.com', 'example.com', true],
            ['example.com.', 'example.com', true],
            ['EXAMPLE.COM', 'example.com', true],
            ['example.com', 'another.com', false],
            ['', '', true],
        ];
    }

    /**
     * @dataProvider providerForGetLevel
     */
    public function testGetLevel(string $domain, int $expectedLevel): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedLevel, $domain->getLevel());
    }

    public static function providerForGetLevel(): array
    {
        return [
            ['ru', 1],
            ['example.ru', 2],
            ['пример.рф', 2],
            ['sub.example.ru', 3],
            ['sub.example.ru.', 3],
        ];
    }

    /**
     * @dataProvider providerForIsValid
     */
    public function testIsValid(string $domain, bool $expectedIsValid): void
    {
        self::assertEquals($expectedIsValid, Domain::isValid($domain));
    }

    public static function providerForIsValid(): array
    {
        return [
            ['com', true],
            ['com.', true],
            ['', true],
            ['.', true],
            [' ', false],
            ['com..', false],
            ['ab--c', false],
            ['аб--в', false],
            ['xn--80acd', true],
            ['xn--80acd.ru', true],
            ['рф', true],
            ['example.com', true],
            ['пример.рф', true],
            ['-', false],
            ['-aaa', false],
            ['aaa-', false],
            ['a-a-a', true],
            ['123', true],
            // maximum length of domain label in punycode - 63
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63', true],
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbcc64', false],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа', true],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50аааааb', false],
            // maximum length of domain in punycode - 253
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbb61', true],
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbb62', false],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааа', true],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50аааа', false],
        ];
    }

    /**
     * @dataProvider providerForGetZone
     */
    public function testGetZone(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getZone());
    }

    public static function providerForGetZone(): array
    {
        return [
            ['com', ''],
            ['com.', ''],
            ['рф', ''],
            ['example.com', 'com'],
            ['example.com.', 'com.'],
            ['пример.рф', 'рф'],
            ['sub.example.com', 'example.com'],
            ['суб.пример.рф', 'пример.рф'],
            ['edu.ru', 'ru'],
            ['example.edu.ru', 'edu.ru'],
            ['sub.example.edu.ru', 'example.edu.ru'],
            ['example.edu.ru.edu.ru', 'edu.ru.edu.ru'],
            ['мск.рф', 'рф'],
            ['пример.мск.рф', 'мск.рф'],
            ['суб.пример.мск.рф', 'пример.мск.рф'],
            ['пример.мск.рф.мск.рф', 'мск.рф.мск.рф'],
            ['example.msk.ru', 'msk.ru'],
            ['com.com', 'com'],
            ['com.com.com', 'com.com'],
        ];
    }

    /**
     * @dataProvider rootDomainProvider
     */
    public function testGetZoneOfRootDomain(string $rootDomain): void
    {
        $this->expectException(RootDomainException::class);
        $domain = new Domain($rootDomain);
        $domain->getZone();
    }

    /**
     * @dataProvider providerForGetWithoutZone
     */
    public function testGetWithoutZone(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getWithoutZone());
    }

    public static function providerForGetWithoutZone(): array
    {
        return [
            ['com', 'com'],
            ['com.', 'com'],
            ['рф', 'рф'],
            ['example.com', 'example'],
            ['example.com.', 'example'],
            ['пример.рф', 'пример'],
            ['sub.example.com', 'sub'],
            ['суб.пример.рф', 'суб'],
            ['edu.ru', 'edu'],
            ['example.edu.ru', 'example'],
            ['sub.example.edu.ru', 'sub'],
            ['example.edu.ru.edu.ru', 'example'],
            ['мск.рф', 'мск'],
            ['пример.мск.рф', 'пример'],
            ['суб.пример.мск.рф', 'суб'],
            ['пример.мск.рф.мск.рф', 'пример'],
            ['example.msk.ru', 'example'],
            ['com.com', 'com'],
            ['com.com.com', 'com'],
        ];
    }

    /**
     * @dataProvider rootDomainProvider
     */
    public function testGetWithoutZoneOfRootDomain(string $rootDomain): void
    {
        $this->expectException(RootDomainException::class);
        $domain = new Domain($rootDomain);
        $domain->getWithoutZone();
    }

    /**
     * @dataProvider providerForGetTld
     */
    public function testGetTld(string $domain, array $customTlds, TldRetrievalMode $mode, ?string $expectedIdn): void
    {
        Domain::setDefaultTldRetrievalMode($mode);

        Domain::setCustomTlds(static function () use ($customTlds) {
            return $customTlds;
        });

        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getTld() ? $domain->getTld() : null);
    }

    public static function providerForGetTld(): array
    {
        return [
            ['com', [], TldRetrievalMode::MIX(), 'com'],
            ['рф', [], TldRetrievalMode::MIX(), 'рф'],
            ['example.com', [], TldRetrievalMode::MIX(), 'com'],
            ['пример.рф', [], TldRetrievalMode::MIX(), 'рф'],
            ['sub.example.com', [], TldRetrievalMode::MIX(), 'com'],
            ['суб.пример.рф', [], TldRetrievalMode::MIX(), 'рф'],
            ['edu.ru', [], TldRetrievalMode::MIX(), 'ru'],
            ['example.edu.ru', [], TldRetrievalMode::MIX(), 'ru'],
            ['example.edu.ru.', [], TldRetrievalMode::MIX(), 'ru'],
            ['sub.example.edu.ru', [], TldRetrievalMode::MIX(), 'ru'],
            ['example.edu.ru.edu.ru', [], TldRetrievalMode::MIX(), 'ru'],
            ['мск.рф', [], TldRetrievalMode::MIX(), 'рф'],
            ['пример.мск.рф', [], TldRetrievalMode::MIX(), 'рф'],
            ['суб.пример.мск.рф', [], TldRetrievalMode::MIX(), 'рф'],
            ['пример.мск.рф.мск.рф', [], TldRetrievalMode::MIX(), 'рф'],
            ['example.msk.ru', [], TldRetrievalMode::MIX(), 'ru'],
            ['com.com', [], TldRetrievalMode::MIX(), 'com'],
            ['com.com.com', [], TldRetrievalMode::MIX(), 'com'],

            ['com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'com'],
            ['рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'рф'],
            ['example.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'com'],
            ['пример.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'рф'],
            ['sub.example.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'com'],
            ['суб.пример.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'рф'],
            ['edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'edu.ru'],
            ['example.edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'edu.ru'],
            ['example.edu.ru.', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'edu.ru'],
            ['sub.example.edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'edu.ru'],
            ['example.edu.ru.edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'edu.ru'],
            ['мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'мск.рф'],
            ['пример.мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'мск.рф'],
            ['суб.пример.мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'мск.рф'],
            ['пример.мск.рф.мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'мск.рф'],
            ['example.msk.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'ru'],
            ['com.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'com'],
            ['com.com.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'com'],

            ['example.edu.ru', [], TldRetrievalMode::MIX(), 'ru'],
            ['example.edu.ru', [], TldRetrievalMode::TRUE(), 'ru'],
            ['example.edu.ru', [], TldRetrievalMode::CUSTOM(), ''],
            ['example.edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::MIX(), 'edu.ru'],
            ['example.edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::TRUE(), 'ru'],
            ['example.edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::CUSTOM(), 'edu.ru'],
            ['edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::CUSTOM(), 'edu.ru'],
        ];
    }

    /**
     * @dataProvider providerForGetTld
     */
    public function testGetTldWithMode(string $domain, array $customTlds, TldRetrievalMode $mode, ?string $expectedIdn): void
    {
        Domain::setCustomTlds(static function () use ($customTlds) {
            return $customTlds;
        });

        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getTld($mode));
    }

    /**
     * @dataProvider rootDomainProvider
     */
    public function testGetTldOfRootDomain(string $rootDomain): void
    {
        $this->expectException(RootDomainException::class);
        $domain = new Domain($rootDomain);
        $domain->getTld();
    }

    /**
     * @dataProvider providerForGetWithoutTld
     */
    public function testGetWithoutTld(string $domain, array $customTlds, TldRetrievalMode $mode, ?string $expectedIdn): void
    {
        Domain::setDefaultTldRetrievalMode($mode);

        Domain::setCustomTlds(static function () use ($customTlds) {
            return $customTlds;
        });

        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getWithoutTld());
    }

    public static function providerForGetWithoutTld(): array
    {
        return [
            ['com', [], TldRetrievalMode::MIX(), null],
            ['рф', [], TldRetrievalMode::MIX(), null],
            ['example.com', [], TldRetrievalMode::MIX(), 'example'],
            ['пример.рф', [], TldRetrievalMode::MIX(), 'пример'],
            ['sub.example.com', [], TldRetrievalMode::MIX(), 'sub.example'],
            ['суб.пример.рф', [], TldRetrievalMode::MIX(), 'суб.пример'],
            ['edu.ru', [], TldRetrievalMode::MIX(), 'edu'],
            ['example.edu.ru', [], TldRetrievalMode::MIX(), 'example.edu'],
            ['example.edu.ru.', [], TldRetrievalMode::MIX(), 'example.edu'],
            ['sub.example.edu.ru', [], TldRetrievalMode::MIX(), 'sub.example.edu'],
            ['example.edu.ru.edu.ru', [], TldRetrievalMode::MIX(), 'example.edu.ru.edu'],
            ['мск.рф', [], TldRetrievalMode::MIX(), 'мск'],
            ['пример.мск.рф', [], TldRetrievalMode::MIX(), 'пример.мск'],
            ['суб.пример.мск.рф', [], TldRetrievalMode::MIX(), 'суб.пример.мск'],
            ['пример.мск.рф.мск.рф', [], TldRetrievalMode::MIX(), 'пример.мск.рф.мск'],
            ['example.msk.ru', [], TldRetrievalMode::MIX(), 'example.msk'],
            ['com.com', [], TldRetrievalMode::MIX(), 'com'],
            ['com.com.com', [], TldRetrievalMode::MIX(), 'com.com'],

            ['com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), null],
            ['рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), null],
            ['example.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'example'],
            ['пример.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'пример'],
            ['sub.example.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'sub.example'],
            ['суб.пример.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'суб.пример'],
            ['edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), null],
            ['example.edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'example'],
            ['example.edu.ru.', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'example'],
            ['sub.example.edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'sub.example'],
            ['example.edu.ru.edu.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'example.edu.ru'],
            ['мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), null],
            ['пример.мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'пример'],
            ['суб.пример.мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'суб.пример'],
            ['пример.мск.рф.мск.рф', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'пример.мск.рф'],
            ['example.msk.ru', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'example.msk'],
            ['com.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'com'],
            ['com.com.com', ['edu.ru', 'мск.рф'], TldRetrievalMode::MIX(), 'com.com'],

            ['example.edu.ru', [], TldRetrievalMode::MIX(), 'example.edu'],
            ['example.edu.ru', [], TldRetrievalMode::TRUE(), 'example.edu'],
            ['example.edu.ru', [], TldRetrievalMode::CUSTOM(), 'example.edu.ru'],
            ['example.edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::MIX(), 'example'],
            ['example.edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::TRUE(), 'example.edu'],
            ['example.edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::CUSTOM(), 'example'],
            ['edu.ru', ['ru', 'edu.ru'], TldRetrievalMode::CUSTOM(), null],
        ];
    }

    /**
     * @dataProvider providerForGetWithoutTld
     */
    public function testGetWithoutTldWithMode(string $domain, array $customTlds, TldRetrievalMode $mode, ?string $expectedIdn): void
    {
        Domain::setCustomTlds(static function () use ($customTlds) {
            return $customTlds;
        });

        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getWithoutTld($mode));
    }

    /**
     * @dataProvider rootDomainProvider
     */
    public function testGetWithoutTldOfRootDomain(string $rootDomain): void
    {
        $this->expectException(RootDomainException::class);
        $domain = new Domain($rootDomain);
        $domain->getWithoutTld();
    }

    /**
     * @dataProvider providerForInvalidDomain
     */
    public function testInvalidDomain(string $domain): void
    {
        $this->expectException(InvalidDomainException::class);
        new Domain($domain);
    }

    public static function providerForInvalidDomain(): array
    {
        return [
            ['  '],
            ['example.com '],
            [' example.com'],
            ['.com'],
            ['ab--c'],
            ['аб--в'],
            ['com..'],
            ['-'],
            ['-.com'],
            ['a-.com'],
            ['-a.com'],
            ['_'],
            ['_.com'],
            ['a_.com'],
            ['_a.com'],
            ['a_b.com'],
            // maximum length of domain label in punycode - 63 (64 in test)
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbcc64'],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50аааааб'],
            // maximum length of domain in punycode - 253 (254 in test)
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbc62'],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50аааа'],
        ];
    }

    /**
     * @dataProvider providerForGetLabels
     */
    public function testGetLabels(string $domain, array $expectedLabels): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedLabels, $domain->getLabels());
    }

    public static function providerForGetLabels(): array
    {
        return [
            ['ru', [new Domain('ru')]],
            ['example.ru', [new Domain('ru'), new Domain('example')]],
            ['sub.example.ru', [new Domain('ru'), new Domain('example'), new Domain('sub')]],
            ['пример.рф', [new Domain('рф'), new Domain('пример')]],
            ['пример.рф', [new Domain('xn--p1ai'), new Domain('xn--e1afmkfd')]],
            ['xn--e1afmkfd.xn--p1ai', [new Domain('рф'), new Domain('пример')]],
            ['xn--e1afmkfd.xn--p1ai', [new Domain('xn--p1ai'), new Domain('xn--e1afmkfd')]],
        ];
    }

    /**
     * @dataProvider providerForGetLabel
     */
    public function testGetLabel(string $domain, int $level, string $expectedLabel): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedLabel, $domain->getLabel($level));
    }

    public static function providerForGetLabel(): array
    {
        return [
            ['sub.domain.com', 1, 'com'],
            ['sub.domain.com', 2, 'domain'],
            ['sub.domain.com', 3, 'sub'],
            ['пример.рф', 1, 'рф'],
            ['пример.рф', 2, 'пример'],
        ];
    }

    /**
     * @dataProvider providerForGetLabelNegative
     */
    public function testGetLabelNegative(string $domain, int $level): void
    {
        $this->expectException(\OutOfRangeException::class);
        $domain = new Domain($domain);
        $domain->getLabel($level);
    }

    public static function providerForGetLabelNegative(): array
    {
        return [
            ['sub.domain.com', -1, 'com'],
            ['sub.domain.com', 0, 'com'],
            ['sub.domain.com', 4, 'domain'],
        ];
    }

    /**
     * @dataProvider providerForSliceToLevel
     */
    public function testSliceToLevel(string $domain, int $level, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->sliceToLevel($level));
    }

    public static function providerForSliceToLevel(): array
    {
        return [
            ['sub.domain.com', 1, 'com'],
            ['sub.domain.com', 2, 'domain.com'],
            ['sub.domain.com', 3, 'sub.domain.com'],
            ['пример.рф', 1, 'рф'],
            ['пример.рф', 2, 'пример.рф'],
        ];
    }

    /**
     * @dataProvider providerForSliceToLevelNegative
     */
    public function testSliceToLevelNegative(string $domain, int $level): void
    {
        $this->expectException(\OutOfRangeException::class);
        $domain = new Domain($domain);
        $domain->sliceToLevel($level);
    }

    public static function providerForSliceToLevelNegative(): array
    {
        return [
            ['sub.domain.com', -1, 'com'],
            ['sub.domain.com', 0, 'com'],
            ['sub.domain.com', 4, 'domain'],
        ];
    }

    /**
     * @dataProvider providerForGetAbsoluteAndGetFqdn
     */
    public function testGetAbsoluteAndGetFqdn(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getAbsolute());
        self::assertEquals($expectedIdn, $domain->getFqdn());
    }

    public static function providerForGetAbsoluteAndGetFqdn(): array
    {
        return [
            ['sub.example.com', 'sub.example.com.'],
            ['sub.example.com.', 'sub.example.com.'],
        ];
    }

    /**
     * @dataProvider providerForIsAbsoluteAndIsFqdn
     */
    public function testIsAbsoluteAndIsFqdn(string $domain, bool $expected): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expected, $domain->isAbsolute());
        self::assertEquals($expected, $domain->isFqdn());
    }

    public static function providerForIsAbsoluteAndIsFqdn(): array
    {
        return [
            ['sub.example.com', false],
            ['sub.example.com.', true],
        ];
    }

    /**
     * @dataProvider providerForGetRelative
     */
    public function testGetRelative(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getRelative());
    }

    public static function providerForGetRelative(): array
    {
        return [
            ['sub.example.com', 'sub.example.com'],
            ['sub.example.com.', 'sub.example.com'],
        ];
    }

    /**
     * @dataProvider providerForIsRoot
     */
    public function testIsRoot(string $domain, bool $expected): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expected, $domain->isRoot());
    }

    public static function providerForIsRoot(): array
    {
        return [
            ['example.com', false],
            ['com', false],
            ['', true],
            ['.', true],
        ];
    }

    /**
     * @dataProvider providerForGetClone
     */
    public function testGetClone(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getClone());
    }

    public static function providerForGetClone(): array
    {
        return [
            ['example.com', 'example.com'],
            ['com', 'com'],
            ['', ''],
            ['.', '.'],
        ];
    }

    public static function rootDomainProvider(): array
    {
        return [
            [''],
            ['.'],
        ];
    }
}
