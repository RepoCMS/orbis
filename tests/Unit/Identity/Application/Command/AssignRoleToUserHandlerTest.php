<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\AssignRoleToUser\AssignRoleToUserCommand;
use App\Identity\Application\Command\AssignRoleToUser\AssignRoleToUserHandler;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class AssignRoleToUserHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const NEW_ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testAssignsRoleSuccessfully(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);
        $userRepo->expects($this->once())->method('save');

        $role = Role::create(
            new RoleIdValueObject(self::NEW_ROLE_ID),
            new RoleNameValueObject('Supervisor'),
        );
        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $handler = new AssignRoleToUserHandler($userRepo, $roleRepo);
        ($handler)(new AssignRoleToUserCommand(self::USER_ID, self::NEW_ROLE_ID));

        self::assertSame(self::NEW_ROLE_ID, (string) $user->roleId());
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn(null);

        $handler = new AssignRoleToUserHandler($userRepo, $this->createMock(RoleRepositoryInterface::class));
        $this->expectException(UserNotFoundException::class);
        ($handler)(new AssignRoleToUserCommand(self::USER_ID, self::NEW_ROLE_ID));
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn(null);

        $handler = new AssignRoleToUserHandler($userRepo, $roleRepo);
        $this->expectException(RoleNotFoundException::class);
        ($handler)(new AssignRoleToUserCommand(self::USER_ID, self::NEW_ROLE_ID));
    }
}
