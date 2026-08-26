<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Enums\RoleStatus;
use App\Identity\Domain\Exceptions\RoleStatusException;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    private function createRole(): Role
    {
        return Role::create(
            new RoleIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new RoleNameValueObject('Administrador'),
            new RoleSlugValueObject('administrador'),
        );
    }

    // --- Creación ---

    public function testCreateRoleWithActiveStatus(): void
    {
        $role = $this->createRole();

        self::assertSame(RoleStatus::ACTIVE, $role->status());
        self::assertTrue($role->isActive());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $role->id());
        self::assertSame('Administrador', (string) $role->name());
        self::assertEmpty($role->permissionIds());
    }

    public function testCreateRoleWithInitialPermissions(): void
    {
        $perm1 = new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1');
        $perm2 = new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA2');

        $role = Role::create(
            new RoleIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
            [$perm1, $perm2],
        );

        self::assertCount(2, $role->permissionIds());
    }

    // --- Rename ---

    public function testRenameChangesName(): void
    {
        $role = $this->createRole();
        $role->rename(new RoleNameValueObject('Supervisor'));

        self::assertSame('Supervisor', (string) $role->name());
    }

    // --- Activate / Deactivate ---

    public function testDeactivateTransitionsToInactive(): void
    {
        $role = $this->createRole();
        $role->deactivate();

        self::assertSame(RoleStatus::INACTIVE, $role->status());
        self::assertFalse($role->isActive());
    }

    public function testDeactivateThrowsWhenAlreadyInactive(): void
    {
        $role = $this->createRole();
        $role->deactivate();

        $this->expectException(RoleStatusException::class);
        $role->deactivate();
    }

    public function testActivateTransitionsToActive(): void
    {
        $role = $this->createRole();
        $role->deactivate();
        $role->activate();

        self::assertSame(RoleStatus::ACTIVE, $role->status());
        self::assertTrue($role->isActive());
    }

    public function testActivateThrowsWhenAlreadyActive(): void
    {
        $role = $this->createRole();

        $this->expectException(RoleStatusException::class);
        $role->activate();
    }

    // --- Permisos ---

    public function testAssignPermissionAddsToCollection(): void
    {
        $role = $this->createRole();
        $permId = new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1');

        $role->assignPermission($permId);

        self::assertCount(1, $role->permissionIds());
        self::assertTrue($role->hasPermission($permId));
    }

    public function testAssignPermissionIsIdempotent(): void
    {
        $role = $this->createRole();
        $permId = new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1');

        $role->assignPermission($permId);
        $role->assignPermission($permId);

        self::assertCount(1, $role->permissionIds());
    }

    public function testRevokePermissionRemovesFromCollection(): void
    {
        $role = $this->createRole();
        $permId = new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1');

        $role->assignPermission($permId);
        $role->revokePermission($permId);

        self::assertCount(0, $role->permissionIds());
        self::assertFalse($role->hasPermission($permId));
    }

    public function testRevokePermissionIsIdempotent(): void
    {
        $role = $this->createRole();
        $permId = new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1');

        $role->revokePermission($permId); // no existe, no lanza error

        self::assertCount(0, $role->permissionIds());
    }

    public function testHasPermissionReturnsFalseWhenNotAssigned(): void
    {
        $role = $this->createRole();
        $permId = new PermissionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1');

        self::assertFalse($role->hasPermission($permId));
    }
}
