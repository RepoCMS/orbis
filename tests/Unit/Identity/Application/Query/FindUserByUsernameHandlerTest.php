<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Application\Query\FindUserByUsername\FindUserByUsernameHandler;
use App\Identity\Application\Query\FindUserByUsername\FindUserByUsernameQuery;
use App\Identity\Domain\Entities\User;
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

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsername')->willReturn($user);

        $handler = new FindUserByUsernameHandler($repo);
        $response = ($handler)(new FindUserByUsernameQuery('juan.perez'));

        self::assertSame('juan.perez', $response->username);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findByUsername')->willReturn(null);

        $handler = new FindUserByUsernameHandler($repo);
        $this->expectException(UserNotFoundException::class);
        ($handler)(new FindUserByUsernameQuery('unknown'));
    }
}
