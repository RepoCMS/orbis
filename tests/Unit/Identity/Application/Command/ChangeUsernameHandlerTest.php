<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\ChangeUsername\ChangeUsernameCommand;
use App\Identity\Application\Command\ChangeUsername\ChangeUsernameHandler;
use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class ChangeUsernameHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testChangesUsernameSuccessfully(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('old.name'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($user);
        $repo->method('findByUsername')->willReturn(null);
        $repo->expects($this->once())->method('save');

        $handler = new ChangeUsernameHandler($repo);
        ($handler)(new ChangeUsernameCommand(self::USER_ID, 'new.name'));

        self::assertSame('new.name', (string) $user->username());
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new ChangeUsernameHandler($repo);
        $this->expectException(UserNotFoundException::class);
        ($handler)(new ChangeUsernameCommand(self::USER_ID, 'new.name'));
    }

    public function testThrowsWhenUsernameTaken(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('old.name'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($user);
        $repo->method('findByUsername')->willReturn(
            User::register(
                new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA2'),
                new UsernameValueObject('taken'),
                new HashedPasswordValueObject('$2y$12$hash'),
                new RoleIdValueObject(self::ROLE_ID),
            ),
        );

        $handler = new ChangeUsernameHandler($repo);
        $this->expectException(UsernameAlreadyExistsException::class);
        ($handler)(new ChangeUsernameCommand(self::USER_ID, 'taken'));
    }
}
