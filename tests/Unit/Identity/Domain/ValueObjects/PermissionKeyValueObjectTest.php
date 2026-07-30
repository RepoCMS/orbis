<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\PermissionKeyException;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class PermissionKeyValueObjectTest extends TestCase
{
    public function testAcceptsValidKey(): void
    {
        $vo = new PermissionKeyValueObject('role.create');
        self::assertSame('role.create', (string) $vo);
    }

    public function testAcceptsKeyWithUnderscore(): void
    {
        $vo = new PermissionKeyValueObject('client_portal.access');
        self::assertSame('client_portal.access', (string) $vo);
    }

    public function testAcceptsKeyWithDigits(): void
    {
        $vo = new PermissionKeyValueObject('module2.action3');
        self::assertSame('module2.action3', (string) $vo);
    }

    public function testNormalizesToLowercase(): void
    {
        $vo = new PermissionKeyValueObject('Role.Create');
        self::assertSame('role.create', (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new PermissionKeyValueObject('  role.create  ');
        self::assertSame('role.create', (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(PermissionKeyException::class);
        new PermissionKeyValueObject('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(PermissionKeyException::class);
        new PermissionKeyValueObject(str_repeat('a.', 51));
    }

    public function testRejectsMissingDot(): void
    {
        $this->expectException(PermissionKeyException::class);
        new PermissionKeyValueObject('rolecreate');
    }

    public function testRejectsMultipleDots(): void
    {
        $this->expectException(PermissionKeyException::class);
        new PermissionKeyValueObject('role.create.test');
    }

    public function testRejectsStartingWithDot(): void
    {
        $this->expectException(PermissionKeyException::class);
        new PermissionKeyValueObject('.role.create');
    }

    public function testRejectsEndingWithDot(): void
    {
        $this->expectException(PermissionKeyException::class);
        new PermissionKeyValueObject('role.create.');
    }
}
