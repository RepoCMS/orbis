<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\AssignPermissionToRole\AssignPermissionToRoleCommand;
use App\Identity\Application\Command\AssignPermissionToRole\AssignPermissionToRoleHandler;
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
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class AssignPermissionToRoleHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testAssignsPermissionSuccessfully(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);
        $roleRepo->expects($this->once())->method('save');

        $permissionRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permissionRepo->method('findById')->willReturn(
            Permission::define(
                new PermissionIdValueObject(self::PERMISSION_ID),
                new PermissionKeyValueObject('role.assign'),
                'Assign permission to role',
            ),
        );

        $handler = new AssignPermissionToRoleHandler($roleRepo, $permissionRepo);
        ($handler)(new AssignPermissionToRoleCommand(self::ROLE_ID, self::PERMISSION_ID));

        self::assertCount(1, $role->permissionIds());
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn(null);

        $handler = new AssignPermissionToRoleHandler($roleRepo, $this->createMock(PermissionRepositoryInterface::class));
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new AssignPermissionToRoleCommand(self::ROLE_ID, self::PERMISSION_ID));
    }

    public function testThrowsWhenPermissionNotFound(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $permissionRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permissionRepo->method('findById')->willReturn(null);

        $handler = new AssignPermissionToRoleHandler($roleRepo, $permissionRepo);
        $this->expectException(PermissionNotFoundException::class);
        ($handler)(new AssignPermissionToRoleCommand(self::ROLE_ID, self::PERMISSION_ID));
    }
}
