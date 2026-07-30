<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserUsernameException;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class UsernameValueObjectTest extends TestCase
{
    public function testAcceptsValidUsername(): void
    {
        $vo = new UsernameValueObject('juan.perez');
        self::assertSame('juan.perez', (string) $vo);
    }

    public function testNormalizesToLowercase(): void
    {
        $vo = new UsernameValueObject('Juan.Perez');
        self::assertSame('juan.perez', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new UsernameValueObject('  juan.perez  ');
        self::assertSame('juan.perez', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(UserUsernameException::class);
        new UsernameValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(UserUsernameException::class);
        new UsernameValueObject('ab');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(UserUsernameException::class);
        new UsernameValueObject(str_repeat('a', 16));
    }

    public function testRejectsInvalidCharacters(): void
    {
        $this->expectException(UserUsernameException::class);
        new UsernameValueObject('user@name');
    }

    public function testFromStringFactory(): void
    {
        $vo = UsernameValueObject::fromString('juan.perez');
        self::assertSame('juan.perez', (string) $vo);
    }
}
