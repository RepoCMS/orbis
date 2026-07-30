<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\RevokeRefreshToken\RevokeRefreshTokenCommand;
use App\Identity\Application\Command\RevokeRefreshToken\RevokeRefreshTokenHandler;
use App\Identity\Domain\Entities\RefreshToken;
use App\Identity\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Identity\Domain\ValueObjects\RefreshTokenIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class RevokeRefreshTokenHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testRevokesTokenSuccessfully(): void
    {
        $token = RefreshToken::create(
            RefreshTokenIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            'some_plain_token',
        );

        $repo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $repo->method('findByHash')->willReturn($token);
        $repo->expects($this->once())->method('save');

        $handler = new RevokeRefreshTokenHandler($repo);
        ($handler)(new RevokeRefreshTokenCommand('token_hash_value'));

        self::assertNotNull($token->revokedAt());
    }

    public function testIsIdempotentWhenTokenNotFound(): void
    {
        $repo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $repo->method('findByHash')->willReturn(null);
        $repo->expects($this->never())->method('save');

        $handler = new RevokeRefreshTokenHandler($repo);
        ($handler)(new RevokeRefreshTokenCommand('non_existent_hash'));

        self::assertTrue(true); // no exception means success
    }
}
