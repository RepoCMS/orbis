<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenCommand;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenHandler;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\Service\AccountNotifierInterface;
use App\Identity\Domain\Service\UserEmailResolverInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class GenerateActivationTokenHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const TOKEN_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA3';

    public function testGeneratesActivationTokenSuccessfully(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->expects($this->once())->method('deleteByUserAndType');
        $tokenRepo->expects($this->once())->method('save');

        $emailResolver = $this->createMock(UserEmailResolverInterface::class);
        $emailResolver->method('resolveEmail')->willReturn('juan@example.com');

        $notifier = $this->createMock(AccountNotifierInterface::class);
        $notifier->expects($this->once())->method('sendActivationEmail');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TOKEN_ID);

        $handler = new GenerateActivationTokenHandler(
            $userRepo,
            $tokenRepo,
            $notifier,
            $emailResolver,
            $idGenerator,
        );
        ($handler)(new GenerateActivationTokenCommand(self::USER_ID));

        self::assertTrue(true);
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn(null);

        $handler = new GenerateActivationTokenHandler(
            $userRepo,
            $this->createMock(UserTokenRepositoryInterface::class),
            $this->createMock(AccountNotifierInterface::class),
            $this->createMock(UserEmailResolverInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );
        $this->expectException(UserNotFoundException::class);
        ($handler)(new GenerateActivationTokenCommand(self::USER_ID));
    }

    public function testThrowsWhenEmailNotFound(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $emailResolver = $this->createMock(UserEmailResolverInterface::class);
        $emailResolver->method('resolveEmail')->willReturn(null);

        $handler = new GenerateActivationTokenHandler(
            $userRepo,
            $this->createMock(UserTokenRepositoryInterface::class),
            $this->createMock(AccountNotifierInterface::class),
            $emailResolver,
            $this->createMock(IdGeneratorInterface::class),
        );
        $this->expectException(\RuntimeException::class);
        ($handler)(new GenerateActivationTokenCommand(self::USER_ID));
    }
}
