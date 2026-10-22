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

    public function testDerivesSlugFromSimpleName(): void
    {
        self::assertSame('administrador', (string) RoleSlugValueObject::fromName('Administrador'));
    }

    public function testDerivesSlugFromAccentedName(): void
    {
        self::assertSame('tecnico', (string) RoleSlugValueObject::fromName('Técnico'));
    }

    public function testDerivesSlugFromMultiWordName(): void
    {
        self::assertSame('tecnico_de_campo', (string) RoleSlugValueObject::fromName('Técnico de Campo'));
    }

    public function testDerivesSlugCollapsingSeparators(): void
    {
        self::assertSame('admin_user', (string) RoleSlugValueObject::fromName('Admin   &&&  User'));
    }

    public function testDerivesSlugPrefixingWhenStartingWithNumber(): void
    {
        self::assertSame('r_2do_nivel', (string) RoleSlugValueObject::fromName('2do Nivel'));
    }

    public function testDerivesSlugTruncatedToMaxLength(): void
    {
        $slug = (string) RoleSlugValueObject::fromName(str_repeat('a', 80));

        self::assertSame(60, mb_strlen($slug));
    }

    public function testDerivesSlugTransliteratingEnye(): void
    {
        self::assertSame('nandu', (string) RoleSlugValueObject::fromName('Ñandú'));
    }

    public function testRejectsNameWithoutAlphanumericCharacters(): void
    {
        $this->expectException(RoleNameException::class);
        RoleSlugValueObject::fromName('###');
    }
}
