<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\RoleNameException;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class RoleSlugValueObjectTest extends TestCase
{
    public function testAcceptsValidSlug(): void
    {
        $vo = new RoleSlugValueObject('admin');
        self::assertSame('admin', (string) $vo);
    }

    public function testAcceptsSlugWithNumbers(): void
    {
        $vo = new RoleSlugValueObject('role-2');
        self::assertSame('role-2', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new RoleSlugValueObject('  admin  ');
        self::assertSame('admin', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleSlugValueObject('');
    }

    public function testRejectsTooShort(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleSlugValueObject('a');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleSlugValueObject(str_repeat('a', 61));
    }

    public function testRejectsStartingWithNumber(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleSlugValueObject('1admin');
    }

    public function testRejectsStartingWithUnderscore(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleSlugValueObject('_admin');
    }

    public function testRejectsUppercase(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleSlugValueObject('Admin');
    }

    public function testRejectsInvalidCharacters(): void
    {
        $this->expectException(RoleNameException::class);
        new RoleSlugValueObject('admin@role');
    }
}
