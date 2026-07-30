<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\VerifyUserByToken\VerifyUserByTokenCommand;
use App\Identity\Application\Command\VerifyUserByToken\VerifyUserByTokenHandler;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Entities\UserToken;
use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Repositories\UserTokenRepositoryInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use PHPUnit\Framework\TestCase;

final class VerifyUserByTokenHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const TOKEN_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA1';
    private const ROLE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FA2';

    public function testVerifiesUserByTokenSuccessfully(): void
    {
        $result = UserToken::generate(
            new UserTokenIdValueObject(self::TOKEN_ID),
            new UserIdValueObject(self::USER_ID),
            UserTokenType::ACTIVATION,
        );
        $tokenEntity = $result['entity'];
        $plainToken = $result['plainToken'];

        $user = User::register(
            new UserIdValueObject(self::USER_ID),
            new UsernameValueObject('juan.perez'),
            new HashedPasswordValueObject('$2y$12$hash'),
            new RoleIdValueObject(self::ROLE_ID),
        );

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->method('findByHash')->willReturn($tokenEntity);
        $tokenRepo->expects($this->once())->method('delete');

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);
        $userRepo->expects($this->once())->method('save');

        $handler = new VerifyUserByTokenHandler($userRepo, $tokenRepo);
        ($handler)(new VerifyUserByTokenCommand((string) $plainToken));

        self::assertSame(UserStatus::ACTIVE, $user->status());
    }

    public function testThrowsWhenTokenNotFound(): void
    {
        $plainToken = bin2hex(random_bytes(32));

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->method('findByHash')->willReturn(null);

        $handler = new VerifyUserByTokenHandler(
            $this->createMock(UserRepositoryInterface::class),
            $tokenRepo,
        );
        $this->expectException(TokenException::class);
        ($handler)(new VerifyUserByTokenCommand($plainToken));
    }

    public function testThrowsWhenTokenWrongType(): void
    {
        $result = UserToken::generate(
            new UserTokenIdValueObject(self::TOKEN_ID),
            new UserIdValueObject(self::USER_ID),
            UserTokenType::PASSWORD_RESET,
        );
        $plainToken = $result['plainToken'];

        $tokenRepo = $this->createMock(UserTokenRepositoryInterface::class);
        $tokenRepo->method('findByHash')->willReturn($result['entity']);

        $handler = new VerifyUserByTokenHandler(
            $this->createMock(UserRepositoryInterface::class),
            $tokenRepo,
        );
        $this->expectException(TokenException::class);
        ($handler)(new VerifyUserByTokenCommand((string) $plainToken));
    }
}
