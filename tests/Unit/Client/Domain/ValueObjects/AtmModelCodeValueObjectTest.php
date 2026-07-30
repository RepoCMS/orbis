<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\AtmModelCodeException;
use App\Client\Domain\ValueObjects\AtmModelCodeValueObject;
use PHPUnit\Framework\TestCase;

final class AtmModelCodeValueObjectTest extends TestCase
{
    public function testAcceptsValidCode(): void
    {
        $vo = new AtmModelCodeValueObject('mx8200st');
        self::assertSame('mx8200st', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new AtmModelCodeValueObject('  mx8200st  ');
        self::assertSame('mx8200st', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(AtmModelCodeException::class);
        new AtmModelCodeValueObject('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(AtmModelCodeException::class);
        new AtmModelCodeValueObject(str_repeat('a', 31));
    }

    public function testAcceptsLongestValid(): void
    {
        $long = str_repeat('a', 30);
        $vo = new AtmModelCodeValueObject($long);
        self::assertSame($long, (string) $vo);
    }

    public function testValueReturnsString(): void
    {
        $vo = new AtmModelCodeValueObject('mx8200st');
        self::assertSame('mx8200st', $vo->value());
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $a = new AtmModelCodeValueObject('mx8200st');
        $b = new AtmModelCodeValueObject('mx8200st');
        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $a = new AtmModelCodeValueObject('mx8200st');
        $b = new AtmModelCodeValueObject('mx5800t');
        self::assertFalse($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentType(): void
    {
        $vo = new AtmModelCodeValueObject('mx8200st');
        self::assertFalse($vo->equals(new \stdClass()));
    }
}
