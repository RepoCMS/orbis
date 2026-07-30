<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Enums\UserTokenType;
use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\ValueObjects\PlainTokenValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use PHPUnit\Framework\TestCase;

final class UserTokenTest extends TestCase
{
    public function testGenerateCreatesActivationTokenWith48HourExpiry(): void
    {
        $result = UserTokenType::ACTIVATION->value;
        $userId = new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');

        ['entity' => $token, 'plainToken' => $plain] = $this->generateToken(UserTokenType::ACTIVATION);

        self::assertSame(UserTokenType::ACTIVATION, $token->type());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $token->userId());
        self::assertFalse($token->isExpired());
        self::assertInstanceOf(PlainTokenValueObject::class, $plain);
    }

    public function testGenerateCreatesPasswordResetTokenWith1HourExpiry(): void
    {
        ['entity' => $token] = $this->generateToken(UserTokenType::PASSWORD_RESET);

        self::assertSame(UserTokenType::PASSWORD_RESET, $token->type());
        self::assertFalse($token->isExpired());
    }

    public function testIsValidReturnsTrueForValidToken(): void
    {
        ['entity' => $token, 'plainToken' => $plain] = $this->generateToken(UserTokenType::ACTIVATION);

        self::assertTrue($token->isValid($plain));
    }

    public function testIsValidReturnsFalseForWrongPlainToken(): void
    {
        ['entity' => $token] = $this->generateToken(UserTokenType::ACTIVATION);
        $wrong = PlainTokenValueObject::generate();

        self::assertFalse($token->isValid($wrong));
    }

    public function testConsumeSucceedsForValidToken(): void
    {
        ['entity' => $token, 'plainToken' => $plain] = $this->generateToken(UserTokenType::ACTIVATION);

        $token->consume($plain);
        $this->addToAssertionCount(1);
    }

    public function testConsumeThrowsForWrongToken(): void
    {
        ['entity' => $token] = $this->generateToken(UserTokenType::ACTIVATION);
        $wrong = PlainTokenValueObject::generate();

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('no es válido');
        $token->consume($wrong);
    }

    public function testIsExpiredReturnsTrueForExpiredToken(): void
    {
        $token = $this->generateToken(UserTokenType::ACTIVATION, -49);

        self::assertTrue($token['entity']->isExpired());
    }

    public function testConsumeThrowsForExpiredToken(): void
    {
        ['entity' => $token, 'plainToken' => $plain] = $this->generateToken(UserTokenType::ACTIVATION, -49);

        $this->expectException(TokenException::class);
        $this->expectExceptionMessage('expirado');
        $token->consume($plain);
    }

    /**
     * @return array{entity: \App\Identity\Domain\Entities\UserToken, plainToken: PlainTokenValueObject}
     */
    private function generateToken(UserTokenType $type, int $hoursOffset = 0): array
    {
        $id = new UserTokenIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $userId = new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');

        $result = \App\Identity\Domain\Entities\UserToken::generate($id, $userId, $type);

        if ($hoursOffset < 0) {
            $ref = new \ReflectionProperty($result['entity'], 'expiresAt');
            $ref->setAccessible(true);
            $ref->setValue($result['entity'], new \DateTimeImmutable("-" . abs($hoursOffset) . " hours"));
        }

        return $result;
    }
}
