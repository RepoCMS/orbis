<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\SerialNumberException;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use PHPUnit\Framework\TestCase;

final class SerialNumberValueObjectTest extends TestCase
{
    public function testAcceptsValidSerial(): void
    {
        $vo = new SerialNumberValueObject('ABC1234567');
        self::assertSame('ABC1234567', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new SerialNumberValueObject('  ABC1234567  ');
        self::assertSame('ABC1234567', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(SerialNumberException::class);
        new SerialNumberValueObject('');
    }

    public function testRejectsWrongLength(): void
    {
        $this->expectException(SerialNumberException::class);
        new SerialNumberValueObject('ABC123456');
    }

    public function testRejectsSpecialCharacters(): void
    {
        $this->expectException(SerialNumberException::class);
        new SerialNumberValueObject('ABC12-4567');
    }

    public function testFromStringFactory(): void
    {
        $vo = SerialNumberValueObject::fromString('ABC1234567');
        self::assertSame('ABC1234567', (string) $vo);
    }
}
