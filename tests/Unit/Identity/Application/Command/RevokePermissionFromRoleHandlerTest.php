<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\RevokePermissionFromRole\RevokePermissionFromRoleCommand;
use App\Identity\Application\Command\RevokePermissionFromRole\RevokePermissionFromRoleHandler;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class RevokePermissionFromRoleHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const PERMISSION_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testRevokesPermissionSuccessfully(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
            [new PermissionIdValueObject(self::PERMISSION_ID)],
        );

        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn($role);
        $repo->expects($this->once())->method('save');

        $handler = new RevokePermissionFromRoleHandler($repo);
        ($handler)(new RevokePermissionFromRoleCommand(self::ROLE_ID, self::PERMISSION_ID));

        self::assertCount(0, $role->permissionIds());
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $repo = $this->createMock(RoleRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new RevokePermissionFromRoleHandler($repo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new RevokePermissionFromRoleCommand(self::ROLE_ID, self::PERMISSION_ID));
    }
}
