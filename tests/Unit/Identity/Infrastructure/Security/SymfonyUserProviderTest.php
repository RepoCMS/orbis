<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Security;

use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Identity\Infrastructure\Security\SymfonyUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class SymfonyUserProviderTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01BXZ4MDEKTSV4RRFFQ69G5ABC';

    private function makeUser(): User
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('test.user'),
            new HashedPasswordValueObject('$2y$10$hashedpassword'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        return $user;
    }

    private function makeProvider(User $user): SymfonyUserProvider
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn($user);

        $role = Role::create(
            new RoleIdValueObject(self::ROLE_ID),
            new RoleNameValueObject('Admin'),
        );

        $roleRepo = $this->createMock(RoleRepositoryInterface::class);
        $roleRepo->method('findById')->willReturn($role);

        $permRepo = $this->createMock(PermissionRepositoryInterface::class);
        $permRepo->method('findByIds')->willReturn([]);

        return new SymfonyUserProvider($userRepo, $roleRepo, $permRepo);
    }

    public function testRefreshUserReturnsFreshUser(): void
    {
        $user = $this->makeUser();
        $provider = $this->makeProvider($user);

        $symfonyUser = new SymfonySecurityUser($user, []);

        $refreshed = $provider->refreshUser($symfonyUser);

        self::assertInstanceOf(SymfonySecurityUser::class, $refreshed);
    }

    public function testLoadUserByIdentifierReturnsSymfonySecurityUser(): void
    {
        $user = $this->makeUser();
        $provider = $this->makeProvider($user);

        $symfonyUser = $provider->loadUserByIdentifier('test.user');

        self::assertInstanceOf(SymfonySecurityUser::class, $symfonyUser);
    }

    public function testDeactivatedUserCannotLoadByIdentifier(): void
    {
        $user = $this->makeUser();
        $user->deactivate();

        $provider = $this->makeProvider($user);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('inactiva');

        $provider->loadUserByIdentifier('test.user');
    }

}
