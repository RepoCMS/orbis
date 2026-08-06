<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\ResetPassword\ResetPasswordCommand;
use App\Identity\Application\Command\ResetPassword\ResetPasswordHandler;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Entities\UserToken;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class ResetPasswordHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const TOKEN_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testResetsPasswordSuccessfully(): void
    {
        $result = UserToken::generate(
            new UserTokenIdValueObject(self::TOKEN_ID),
            new UserIdValueObject(self::USER_ID),
            UserTokenType::PASSWORD_RESET,
        );
        $tokenEntity = $result['entity'];
        $plainToken = $result['plainToken'];

        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$oldhash'),
            new RoleIdValueObject(self::ROLE_ID),
        );
        $user->activate();

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->method('findByHash')->willReturn($tokenEntity);
        $tokenRepo->expects($this->once())->method('delete');

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);
        $userRepo->expects($this->once())->method('save');

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn(new HashedPasswordValueObject('$2y$12$newhash'));

        $publisher = $this->createMock(DomainEventPublisherInterface::class);
        $publisher->expects($this->once())->method('publish');

        $authLogRepo = $this->createMock(AuthLogRepositoryInterface::class);
        $authLogRepo->expects($this->once())->method('append');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::TOKEN_ID);

        $handler = new ResetPasswordHandler(
            $tokenRepo,
            $userRepo,
            $hasher,
            $publisher,
            $authLogRepo,
            $idGenerator,
        );
        ($handler)(new ResetPasswordCommand((string) $plainToken, 'NewSecurePass1!'));

        self::assertSame('$2y$12$newhash', (string) $user->password());
    }

    public function testThrowsWhenTokenNotFound(): void
    {
        $plainToken = bin2hex(random_bytes(32));

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->method('findByHash')->willReturn(null);

        $handler = new ResetPasswordHandler(
            $tokenRepo,
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(PasswordHasherInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(AuthLogRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );
        $this->expectException(TokenException::class);
        ($handler)(new ResetPasswordCommand($plainToken, 'NewSecurePass1!'));
    }

    public function testThrowsWhenTokenWrongType(): void
    {
        $result = UserToken::generate(
            new UserTokenIdValueObject(self::TOKEN_ID),
            new UserIdValueObject(self::USER_ID),
            UserTokenType::ACTIVATION,
        );

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->method('findByHash')->willReturn($result['entity']);

        $handler = new ResetPasswordHandler(
            $tokenRepo,
            $this->createMock(UserRepositoryInterface::class),
            $this->createMock(PasswordHasherInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->createMock(AuthLogRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
        );
        $this->expectException(TokenException::class);
        ($handler)(new ResetPasswordCommand((string) $result['plainToken'], 'NewSecurePass1!'));
    }
}
