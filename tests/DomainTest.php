<?php

declare(strict_types=1);

namespace Rincler\Tests;

use PHPUnit\Framework\TestCase;
use Rincler\Domain\Domain;
use Rincler\Domain\InvalidDomainException;

class DomainTest extends TestCase
{
    /**
     * @dataProvider providerForGetIdnAndGetPunycode
     */
    public function testGetIdn(string $domain, string $expectedIdn, string $exptectedPunycode): void
    {
        $domain = new Domain($domain);

        $this->assertEquals($expectedIdn, $domain->asIdn());
    }

    /**
     * @dataProvider providerForGetIdnAndGetPunycode
     */
    public function testGetPunycode(string $domain, string $expectedIdn, string $exptectedPunycode): void
    {
        $domain = new Domain($domain);

        $this->assertEquals($exptectedPunycode, $domain->asPunycode());
    }

    public function providerForGetIdnAndGetPunycode(): array
    {
        return [
            ['com', 'com', 'com'],
            ['рф', 'рф', 'xn--p1ai'],
            ['asd.com', 'asd.com', 'asd.com'],
            ['фыв.рф', 'фыв.рф', 'xn--b1a9av.xn--p1ai'],
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
     * @dataProvider providerForGetLevel
     */
    public function testGetLevel(string $domain, int $expectedLevel): void
    {
        $domain = new Domain($domain);

        $this->assertEquals($expectedLevel, $domain->getLevel());
    }

    public function providerForGetLevel(): array
    {
        return [
            ['ru', 1],
            ['asd.ru', 2],
            ['фыв.рф', 2],
            ['asd.fgh.ru', 3],
        ];
    }

    /**
     * @dataProvider providerForIsValid
     */
    public function testIsValid(string $domain, bool $expectedIsValid): void
    {
        $this->assertEquals($expectedIsValid, Domain::isValid($domain));
    }

    public function providerForIsValid(): array
    {
        return [
            ['', false],
            ['com', true],
            ['com.', true],
            ['com..', false],
            ['.', false],
            ['ab--c', false],
            ['аб--в', false],
            ['xn--80acd', true],
            ['xn--80acd.ru', true],
            ['рф', true],
            ['asd.com', true],
            ['фыв.рф', true],
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

        $this->assertEquals($expectedIdn, $domain->getZone() !== null ? $domain->getZone()->asIdn() : '');
    }

    public function providerForGetZone(): array
    {
        return [
            ['com', ''],
            ['рф', ''],
            ['asd.com', 'com'],
            ['фыв.рф', 'рф'],
            ['asd.qwe.com', 'qwe.com'],
            ['фыв.апр.рф', 'апр.рф'],
            ['asd.msk.ru', 'msk.ru'],
        ];
    }

    /**
     * @dataProvider providerForGetWithoutZone
     */
    public function testGetWithoutZone(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        $this->assertEquals($expectedIdn, $domain->getWithoutZone() !== null ? $domain->getWithoutZone()->asIdn() : '');
    }

    public function providerForGetWithoutZone(): array
    {
        return [
            ['com', ''],
            ['рф', ''],
            ['asd.com', 'asd'],
            ['фыв.рф', 'фыв'],
            ['asd.qwe.com', 'asd'],
            ['фыв.апр.рф', 'фыв'],
            ['asd.msk.ru', 'asd'],
        ];
    }

    /**
     * @dataProvider providerForGetTld
     */
    public function testGetTld(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        $this->assertEquals($expectedIdn, $domain->getTld() !== null ? $domain->getTld()->asIdn() : '');
    }

    public function providerForGetTld(): array
    {
        return [
            ['com', ''],
            ['рф', ''],
            ['asd.com', 'com'],
            ['фыв.рф', 'рф'],
            ['asd.qwe.com', 'com'],
            ['фыв.апр.рф', 'рф'],
            ['asd.msk.ru', 'ru'],
        ];
    }

    /**
     * @dataProvider providerForGetWithoutTld
     */
    public function testGetWithoutTld(string $domain, string $expectedIdn): void
    {
        $domain = new Domain($domain);

        $this->assertEquals($expectedIdn, $domain->getWithoutTld() !== null ? $domain->getWithoutTld()->asIdn() : '');
    }

    public function providerForGetWithoutTld(): array
    {
        return [
            ['com', ''],
            ['рф', ''],
            ['asd.com', 'asd'],
            ['фыв.рф', 'фыв'],
            ['asd.qwe.com', 'asd.qwe'],
            ['фыв.апр.рф', 'фыв.апр'],
            ['asd.msk.ru', 'asd.msk'],
        ];
    }

    /**
     * @dataProvider providerForInvalidDomain
     */
    public function testInvalidDomain(string $domain): void
    {
        $this->expectException(InvalidDomainException::class);
        new Domain($domain);
    }

    public function providerForInvalidDomain(): array
    {
        return [
            [''],
            ['.com'],
            ['.'],
            ['ab--c'],
            ['аб--в'],
            ['com..'],
            // maximum length of domain label in punycode - 63 (64 in test)
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbcc64'],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50аааааб'],
            // maximum length of domain in punycode - 253 (254 in test)
            ['aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbbc63.aaaaabbbbbcccccdddddeee25aaaaabbbbbcccccdddddeee50aaaaabbbbc62'],
            ['ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50ааааа.ааааабббббвввввгггггддд25ааааабббббвввввгггггддд50аааа'],
        ];
    }

    public function testEqualsDomain(): void
    {
        $domainA = new Domain('abc.ru');
        $domainB = new Domain('abc.ru');
        $domainC = new Domain('abcd.ru');

        $this->assertTrue($domainA->equals($domainB));
        $this->assertFalse($domainA->equals($domainC));
    }
}
