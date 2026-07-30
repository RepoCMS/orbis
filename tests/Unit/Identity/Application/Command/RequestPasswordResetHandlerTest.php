<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\RequestPasswordReset\RequestPasswordResetCommand;
use App\Identity\Application\Command\RequestPasswordReset\RequestPasswordResetHandler;
use App\Identity\Application\Exception\EmailNotFoundException;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Service\ForgotPasswordEmailSenderInterface;
use App\Identity\Domain\Service\UserByEmailResolverInterface;
use App\Identity\Domain\Service\UserEmailResolverInterface;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RequestPasswordResetHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const TOKEN_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testRequestsPasswordResetSuccessfully(): void
    {
        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );

        $emailResolver = $this->createMock(UserByEmailResolverInterface::class);
        $emailResolver->method('resolveByEmail')->willReturn(new UserIdValueObject(self::USER_ID));

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->expects($this->once())->method('deleteByUserAndType');
        $tokenRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TOKEN_ID);

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish');

        $emailSender = $this->createMock(ForgotPasswordEmailSenderInterface::class);
        $emailSender->expects($this->once())->method('sendPasswordResetEmail');

        $userEmailResolver = $this->createMock(UserEmailResolverInterface::class);
        $userEmailResolver->method('resolveEmail')->willReturn('juan@example.com');

        $authLogRepo = $this->createMock(AuthLogRepositoryInterface::class);
        $authLogRepo->expects($this->once())->method('append');

        $handler = new RequestPasswordResetHandler(
            $emailResolver, $userRepo, $tokenRepo, $idGenerator, $publisher,
            $emailSender, $userEmailResolver, $authLogRepo,
        );
        ($handler)(new RequestPasswordResetCommand('juan@example.com'));

        self::assertTrue(true);
    }

    public function testThrowsWhenEmailNotFound(): void
    {
        $emailResolver = $this->createMock(UserByEmailResolverInterface::class);
        $emailResolver->method('resolveByEmail')->willReturn(null);

        $handler = new RequestPasswordResetHandler(
            $emailResolver,
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(UserTokenRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(ForgotPasswordEmailSenderInterface::class),
            $this->createMock(UserEmailResolverInterface::class),
            $this->createMock(AuthLogRepositoryInterface::class),
        );
        $this->expectException(EmailNotFoundException::class);
        ($handler)(new RequestPasswordResetCommand('unknown@example.com'));
    }

    public function testDoesNotSendEmailWhenUserEmailNotResolved(): void
    {
        $emailResolver = $this->createMock(UserByEmailResolverInterface::class);
        $emailResolver->method('resolveByEmail')->willReturn(new UserIdValueObject(self::USER_ID));

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn(null);

        $handler = new RequestPasswordResetHandler(
            $emailResolver,
            $userRepo,
            $this->createMock(UserTokenRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(ForgotPasswordEmailSenderInterface::class),
            $this->createMock(UserEmailResolverInterface::class),
            $this->createMock(AuthLogRepositoryInterface::class),
        );
        $this->expectException(EmailNotFoundException::class);
        ($handler)(new RequestPasswordResetCommand('juan@example.com'));
    }
}
