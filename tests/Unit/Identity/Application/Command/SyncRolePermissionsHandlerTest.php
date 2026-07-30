<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsCommand;
use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsHandler;
use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use PHPUnit\Framework\TestCase;

final class SyncRolePermissionsHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const PERMISSION_OLD = '01ARZ3NDEKTSV4RRFFQ69G5FA2';
    private const PERMISSION_NEW = '01ARZ3NDEKTSV4RRFFQ69G5FA3';

    public function testSyncsPermissionsSuccessfully(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            [new PermissionIdValueObject(self::PERMISSION_OLD)],
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);
        $roleRepo->expects($this->once())->method('save');

        $permissionRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permissionRepo->method('findById')->willReturn(
            Permission::define(
                new PermissionIdValueObject(self::PERMISSION_NEW),
                new PermissionKeyValueObject('role.sync'),
                'Sync permission',
            ),
        );

        $handler = new SyncRolePermissionsHandler($roleRepo, $permissionRepo);
        ($handler)(new SyncRolePermissionsCommand(self::ROLE_ID, [self::PERMISSION_NEW]));

        self::assertCount(1, $role->permissionIds());
        self::assertSame(self::PERMISSION_NEW, (string) $role->permissionIds()[0]);
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn(null);

        $handler = new SyncRolePermissionsHandler($roleRepo, $this->createMock(PermissionRepositoryInterface::class));
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new SyncRolePermissionsCommand(self::ROLE_ID, [self::PERMISSION_NEW]));
    }

    public function testThrowsWhenPermissionNotFound(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $permissionRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permissionRepo->method('findById')->willReturn(null);

        $handler = new SyncRolePermissionsHandler($roleRepo, $permissionRepo);
        $this->expectException(PermissionNotFoundException::class);
        ($handler)(new SyncRolePermissionsCommand(self::ROLE_ID, [self::PERMISSION_NEW]));
    }
}
