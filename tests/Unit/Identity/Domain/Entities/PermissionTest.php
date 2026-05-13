<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    private function createPermission(): Permission
    {
        return Permission::define(
            new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new PermissionKeyValueObject('user.create'),
            'Permite crear usuarios',
        );
    }

    public function testDefineCreatesPermissionWithAllFields(): void
    {
        $permission = $this->createPermission();

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $permission->id());
        self::assertSame('user.create', (string) $permission->key());
        self::assertSame('Permite crear usuarios', $permission->description());
    }

    public function testChangeDescriptionUpdatesValue(): void
    {
        $permission = $this->createPermission();

        $permission->changeDescription('Nueva descripción del permiso');

        self::assertSame('Nueva descripción del permiso', $permission->description());
    }

    public function testChangeDescriptionTrimsWhitespace(): void
    {
        $permission = $this->createPermission();

        $permission->changeDescription('  descripción con espacios  ');

        self::assertSame('descripción con espacios', $permission->description());
    }
}
