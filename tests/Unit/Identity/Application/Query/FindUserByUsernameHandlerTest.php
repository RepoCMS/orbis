<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Application\Query\FindUserByUsername\FindUserByUsernameHandler;
use App\Identity\Application\Query\FindUserByUsername\FindUserByUsernameQuery;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class FindUserByUsernameHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testFindsUserByUsername(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn($user);

        $mfaRepo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $mfaRepo->method('findByUserId')->willReturn(null);

        $handler = new FindUserByUsernameHandler($userRepo, $mfaRepo);
        $response = ($handler)(new FindUserByUsernameQuery('juan.perez'));

        self::assertSame('juan.perez', $response->username);
        self::assertFalse($response->mfaEnabled);
    }

    public function testThrowsWhenNotFound(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findByUsername')->willReturn(null);

        $mfaRepo = $this->createMock(UserMfaConfigRepositoryInterface::class);

        $handler = new FindUserByUsernameHandler($userRepo, $mfaRepo);
        $this->expectException(UserNotFoundException::class);
        ($handler)(new FindUserByUsernameQuery('unknown'));
    }
}
