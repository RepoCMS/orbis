<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\UnlockUser\UnlockUserCommand;
use App\Identity\Application\Command\UnlockUser\UnlockUserHandler;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class UnlockUserHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testUnlocksUserSuccessfully(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();
        for ($i = 0; $i < 5; $i++) {
            $user->recordFailedLogin();
        }

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($user);
        $repo->expects($this->once())->method('save');

        $handler = new UnlockUserHandler($repo);
        ($handler)(new UnlockUserCommand(self::USER_ID));

        self::assertFalse($user->isLocked());
        self::assertSame(0, $user->failedLoginAttempts());
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new UnlockUserHandler($repo);
        $this->expectException(UserNotFoundException::class);
        ($handler)(new UnlockUserCommand(self::USER_ID));
    }
}
