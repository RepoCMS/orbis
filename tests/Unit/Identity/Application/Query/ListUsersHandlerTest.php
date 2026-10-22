<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Query\ListUsers\ListUsersHandler;
use App\Identity\Application\Query\ListUsers\ListUsersQuery;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class ListUsersHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testListsAllUsers(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findAll')->willReturn([$user]);

        $mfaRepo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $mfaRepo->method('findByUserId')->willReturn(null);

        $handler = new ListUsersHandler($userRepo, $mfaRepo);
        $result = ($handler)(new ListUsersQuery());

        self::assertCount(1, $result);
        self::assertSame(self::USER_ID, $result[0]->id);
        self::assertFalse($result[0]->mfaEnabled);
    }

    public function testReturnsEmptyArrayWhenNoUsers(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findAll')->willReturn([]);

        $mfaRepo = $this->createMock(UserMfaConfigRepositoryInterface::class);

        $handler = new ListUsersHandler($userRepo, $mfaRepo);
        $result = ($handler)(new ListUsersQuery());

        self::assertEmpty($result);
    }
}
