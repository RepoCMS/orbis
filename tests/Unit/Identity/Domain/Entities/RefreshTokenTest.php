<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Entities\RefreshToken;
use App\Identity\Domain\ValueObjects\RefreshTokenIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class RefreshTokenTest extends TestCase
{
    public function testCreateStoresAllFields(): void
    {
        $token = RefreshToken::create(
            new RefreshTokenIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            'plain-token-value',
        );

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $token->id());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FA1', (string) $token->userId());
        self::assertSame(hash('sha256', 'plain-token-value'), $token->tokenHash());
        self::assertNotNull($token->createdAt());
        self::assertNull($token->revokedAt());
    }

    public function testIsValidReturnsTrueForActiveToken(): void
    {
        $token = $this->createToken();

        self::assertTrue($token->isValid());
    }

    public function testIsValidReturnsFalseAfterRevoke(): void
    {
        $token = $this->createToken();
        $token->revoke();

        self::assertFalse($token->isValid());
    }

    public function testRevokeSetsRevokedAt(): void
    {
        $token = $this->createToken();
        $token->revoke();

        self::assertNotNull($token->revokedAt());
        self::assertFalse($token->isValid());
    }

    public function testRevokeIsIdempotent(): void
    {
        $token = $this->createToken();
        $token->revoke();
        $token->revoke();

        self::assertNotNull($token->revokedAt());
        self::assertFalse($token->isValid());
    }

    private function createToken(): RefreshToken
    {
        return RefreshToken::create(
            new RefreshTokenIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            'test-plain-token',
        );
    }
}
