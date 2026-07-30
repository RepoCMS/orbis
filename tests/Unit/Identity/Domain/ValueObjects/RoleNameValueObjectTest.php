<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\RoleNameException;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use PHPUnit\Framework\TestCase;

final class RoleNameValueObjectTest extends TestCase
{
    public function testAcceptsValidName(): void
    {
        $vo = new RoleNameValueObject('Admin');
        self::assertSame('Admin', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new RoleNameValueObject('  Admin  ');
        self::assertSame('Admin', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleNameValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleNameValueObject('A');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleNameValueObject(str_repeat('a', 51));
    }

    public function testAcceptsBoundaryValues(): void
    {
        $vo = new RoleNameValueObject('Ad');
        self::assertSame('Ad', (string) $vo);

        $long = str_repeat('a', 50);
        $vo2 = new RoleNameValueObject($long);
        self::assertSame($long, (string) $vo2);
    }
}
