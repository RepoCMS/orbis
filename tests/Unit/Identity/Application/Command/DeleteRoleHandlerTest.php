<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\DeleteRole\DeleteRoleCommand;
use App\Identity\Application\Command\DeleteRole\DeleteRoleHandler;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Exceptions\RoleStatusException;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use PHPUnit\Framework\TestCase;

final class DeleteRoleHandlerTest extends TestCase
{
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testDeletesRoleSuccessfully(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);
        $roleRepo->expects($this->once())->method('save');

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('countByRoleId')->willReturn(0);

        $handler = new DeleteRoleHandler($roleRepo, $userRepo);
        ($handler)(new DeleteRoleCommand(self::ROLE_ID));

        self::assertFalse($role->isActive());
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn(null);

        $handler = new DeleteRoleHandler($roleRepo, $this->createMock(UserRepositoryInterface::class));
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new DeleteRoleCommand(self::ROLE_ID));
    }

    public function testThrowsWhenRoleHasUsers(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('countByRoleId')->willReturn(3);

        $handler = new DeleteRoleHandler($roleRepo, $userRepo);
        $this->expectException(\DomainException::class);
        ($handler)(new DeleteRoleCommand(self::ROLE_ID));
    }

    public function testThrowsWhenRoleAlreadyInactive(): void
    {
        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
            new RoleSlugValueObject('admin'),
        );
        $role->deactivate();

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('countByRoleId')->willReturn(0);

        $handler = new DeleteRoleHandler($roleRepo, $userRepo);
        $this->expectException(RoleStatusException::class);
        ($handler)(new DeleteRoleCommand(self::ROLE_ID));
    }
}
