<?php

declare(strict_types=1);

namespace Rincler\Tests;

use PHPUnit\Framework\TestCase;
use Rincler\Domain\Domain;
use Rincler\Domain\InvalidDomainException;
use Rincler\Domain\RootDomainException;

class DomainTest extends TestCase
{
    /**
     * @dataProvider providerForConstruct
     */
    public function testConstruct(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain);
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
     * @dataProvider providerForGetTld
     */
    public function testGetTld(string $domain, ?string $expectedIdn): void
    {
        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getTld());
    }

    public static function providerForGetTld(): array
    {
        return [
            ['com', 'com'],
            ['рф', 'рф'],
            ['example.com', 'com'],
            ['пример.рф', 'рф'],
            ['sub.example.com', 'com'],
            ['суб.пример.рф', 'рф'],
            ['edu.ru', 'ru'],
            ['example.edu.ru', 'ru'],
            ['example.edu.ru.', 'ru'],
            ['sub.example.edu.ru', 'ru'],
            ['example.edu.ru.edu.ru', 'ru'],
            ['мск.рф', 'рф'],
            ['пример.мск.рф', 'рф'],
            ['суб.пример.мск.рф', 'рф'],
            ['пример.мск.рф.мск.рф', 'рф'],
            ['example.msk.ru', 'ru'],
            ['com.com', 'com'],
            ['com.com.com', 'com'],
            ['example.net', 'net'],
        ];
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
     * @dataProvider providerForGetETld
     */
    public function testGetETld(string $domain, ?string $expectedIdn): void
    {
        Domain::setETldProvider(static function () {
            return ['com', 'ru', 'рф', 'edu.ru', 'мск.рф'];
        });

        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getETld());
    }

    public static function providerForGetETld(): array
    {
        return [
            ['com', 'com'],
            ['рф', 'рф'],
            ['example.com', 'com'],
            ['пример.рф', 'рф'],
            ['sub.example.com', 'com'],
            ['суб.пример.рф', 'рф'],
            ['edu.ru', 'edu.ru'],
            ['example.edu.ru', 'edu.ru'],
            ['example.edu.ru.', 'edu.ru'],
            ['sub.example.edu.ru', 'edu.ru'],
            ['example.edu.ru.edu.ru', 'edu.ru'],
            ['мск.рф', 'мск.рф'],
            ['пример.мск.рф', 'мск.рф'],
            ['суб.пример.мск.рф', 'мск.рф'],
            ['пример.мск.рф.мск.рф', 'мск.рф'],
            ['example.msk.ru', 'ru'],
            ['com.com', 'com'],
            ['com.com.com', 'com'],
            ['example.net', null],
        ];
    }

    /**
     * @dataProvider rootDomainProvider
     */
    public function testGetETldOfRootDomain(string $rootDomain): void
    {
        $this->expectException(RootDomainException::class);
        $domain = new Domain($rootDomain);
        $domain->getETld();
    }

    /**
     * @dataProvider providerForGetETldPlusOne
     */
    public function testGetETldPlusOne(string $domain, ?string $expectedIdn): void
    {
        Domain::setETldProvider(static function () {
            return ['com', 'ru', 'рф', 'edu.ru', 'мск.рф'];
        });

        $domain = new Domain($domain);

        self::assertEquals($expectedIdn, $domain->getETldPlusOne());
    }

    public static function providerForGetETldPlusOne(): array
    {
        return [
            ['com', null],
            ['рф', null],
            ['example.com', 'example.com'],
            ['пример.рф', 'пример.рф'],
            ['sub.example.com', 'example.com'],
            ['суб.пример.рф', 'пример.рф'],
            ['edu.ru', null],
            ['example.edu.ru', 'example.edu.ru'],
            ['example.edu.ru.', 'example.edu.ru'],
            ['sub.example.edu.ru', 'example.edu.ru'],
            ['example.edu.ru.edu.ru', 'ru.edu.ru'],
            ['мск.рф', null],
            ['пример.мск.рф', 'пример.мск.рф'],
            ['суб.пример.мск.рф', 'пример.мск.рф'],
            ['пример.мск.рф.мск.рф', 'рф.мск.рф'],
            ['example.msk.ru', 'msk.ru'],
            ['com.com', 'com.com'],
            ['com.com.com', 'com.com'],
            ['example.net', null],
        ];
    }

    /**
     * @dataProvider rootDomainProvider
     */
    public function testGetETldPlusOneOfRootDomain(string $rootDomain): void
    {
        $this->expectException(RootDomainException::class);
        $domain = new Domain($rootDomain);
        $domain->getETldPlusOne();
    }

    /**
     * @dataProvider providerForGetWithout
     */
    public function testGetWithout(string $domain, string $without, ?string $expectedIdn): void
    {
        Domain::setETldProvider(static function () {
            return ['com', 'ru', 'рф', 'edu.ru', 'мск.рф'];
        });

        $domain = new Domain($domain);

        switch ($without) {
            case 'zone':
                $suffix = $domain->getZone();
                break;
            case 'TLD':
                $suffix = $domain->getTld();
                break;
            case 'eTLD':
                $suffix = $domain->getETld();
                break;
            case 'eTLD+1':
                $suffix = $domain->getETldPlusOne();
                break;
            default:
                $suffix = new Domain($without);
        }

        self::assertEquals($expectedIdn, $domain->getWithout($suffix));
    }

    public static function providerForGetWithout(): array
    {
        return [
            ['example.com', '', 'example.com'],
            ['example.com', '.', 'example.com'],
            ['example.com.', '', 'example.com'],
            ['example.com.', '.', 'example.com'],
            ['example.com', 'sub.example.com', 'example.com'],
            ['example.com.', 'sub.example.com', 'example.com'],
            ['com', 'zone', 'com'],
            ['com.', 'zone', 'com'],
            ['рф', 'zone', 'рф'],
            ['example.com', 'zone', 'example'],
            ['example.com.', 'zone', 'example'],
            ['пример.рф', 'zone', 'пример'],
            ['sub.example.com', 'zone', 'sub'],
            ['суб.пример.рф', 'zone', 'суб'],
            ['edu.ru', 'zone', 'edu'],
            ['example.edu.ru', 'zone', 'example'],
            ['example.edu.ru.', 'zone', 'example'],
            ['sub.example.edu.ru', 'zone', 'sub'],
            ['example.edu.ru.edu.ru', 'zone', 'example'],
            ['мск.рф', 'zone', 'мск'],
            ['пример.мск.рф', 'zone', 'пример'],
            ['суб.пример.мск.рф', 'zone', 'суб'],
            ['пример.мск.рф.мск.рф', 'zone', 'пример'],
            ['example.msk.ru', 'zone', 'example'],
            ['com.com', 'zone', 'com'],
            ['com.com.com', 'zone', 'com'],
            ['example.net', 'zone', 'example'],
            ['com', 'TLD', null],
            ['com.', 'TLD', null],
            ['рф', 'TLD', null],
            ['example.com', 'TLD', 'example'],
            ['example.com.', 'TLD', 'example'],
            ['пример.рф', 'TLD', 'пример'],
            ['sub.example.com', 'TLD', 'sub.example'],
            ['суб.пример.рф', 'TLD', 'суб.пример'],
            ['edu.ru', 'TLD', 'edu'],
            ['example.edu.ru', 'TLD', 'example.edu'],
            ['example.edu.ru.', 'TLD', 'example.edu'],
            ['sub.example.edu.ru', 'TLD', 'sub.example.edu'],
            ['example.edu.ru.edu.ru', 'TLD', 'example.edu.ru.edu'],
            ['мск.рф', 'TLD', 'мск'],
            ['пример.мск.рф', 'TLD', 'пример.мск'],
            ['суб.пример.мск.рф', 'TLD', 'суб.пример.мск'],
            ['пример.мск.рф.мск.рф', 'TLD', 'пример.мск.рф.мск'],
            ['example.msk.ru', 'TLD', 'example.msk'],
            ['com.com', 'TLD', 'com'],
            ['com.com.com', 'TLD', 'com.com'],
            ['example.net', 'TLD', 'example'],
            ['com', 'eTLD',  null],
            ['com.', 'eTLD', null],
            ['рф', 'eTLD', null],
            ['example.com', 'eTLD', 'example'],
            ['example.com.', 'eTLD', 'example'],
            ['пример.рф', 'eTLD', 'пример'],
            ['sub.example.com', 'eTLD', 'sub.example'],
            ['суб.пример.рф', 'eTLD', 'суб.пример'],
            ['edu.ru', 'eTLD', null],
            ['example.edu.ru', 'eTLD', 'example'],
            ['example.edu.ru.', 'eTLD', 'example'],
            ['sub.example.edu.ru', 'eTLD', 'sub.example'],
            ['example.edu.ru.edu.ru', 'eTLD', 'example.edu.ru'],
            ['мск.рф', 'eTLD', null],
            ['пример.мск.рф', 'eTLD', 'пример'],
            ['суб.пример.мск.рф', 'eTLD', 'суб.пример'],
            ['пример.мск.рф.мск.рф', 'eTLD', 'пример.мск.рф'],
            ['example.msk.ru', 'eTLD', 'example.msk'],
            ['com.com', 'eTLD', 'com'],
            ['com.com.com', 'eTLD', 'com.com'],
            ['example.net', 'eTLD', 'example.net'],
            ['com', 'eTLD+1', 'com'],
            ['com.', 'eTLD+1', 'com'],
            ['рф', 'eTLD+1', 'рф'],
            ['example.com', 'eTLD+1', null],
            ['example.com.', 'eTLD+1', null],
            ['пример.рф', 'eTLD+1', null],
            ['sub.example.com', 'eTLD+1', 'sub'],
            ['суб.пример.рф', 'eTLD+1', 'суб'],
            ['edu.ru', 'eTLD+1', 'edu.ru'],
            ['example.edu.ru', 'eTLD+1', null],
            ['example.edu.ru.', 'eTLD+1', null],
            ['sub.example.edu.ru', 'eTLD+1', 'sub'],
            ['example.edu.ru.edu.ru', 'eTLD+1', 'example.edu'],
            ['мск.рф', 'eTLD+1', 'мск.рф'],
            ['пример.мск.рф', 'eTLD+1', null],
            ['суб.пример.мск.рф', 'eTLD+1', 'суб'],
            ['пример.мск.рф.мск.рф', 'eTLD+1', 'пример.мск'],
            ['example.msk.ru', 'eTLD+1', 'example'],
            ['com.com', 'eTLD+1', null],
            ['com.com.com', 'eTLD+1', 'com'],
            ['example.net', 'eTLD+1', 'example.net'],
        ];
    }

    /**
     * @dataProvider rootDomainProvider
     */
    public function testGetWithoutOfRootDomain(string $rootDomain): void
    {
        $this->expectException(RootDomainException::class);
        $domain = new Domain($rootDomain);
        $domain->getWithout(new Domain('com'));
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
