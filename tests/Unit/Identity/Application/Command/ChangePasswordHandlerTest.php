<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\ChangePassword\ChangePasswordCommand;
use App\Identity\Application\Command\ChangePassword\ChangePasswordHandler;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class ChangePasswordHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';

    public function testChangesPasswordSuccessfully(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$oldhash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($user);
        $repo->expects($this->once())->method('save');

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn(new HashedPasswordValueObject('$2y$12$newhash'));

        $handler = new ChangePasswordHandler($repo, $hasher);
        ($handler)(new ChangePasswordCommand(self::USER_ID, 'NewSecurePass1!'));

        self::assertSame('$2y$12$newhash', (string) $user->password());
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new ChangePasswordHandler($repo, $this->createMock(PasswordHasherInterface::class));
        $this->expectException(UserNotFoundException::class);
        ($handler)(new ChangePasswordCommand(self::USER_ID, 'NewSecurePass1!'));
    }
}
