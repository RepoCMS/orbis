<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Entities\UserMfaBackupCode;
use App\Identity\Domain\ValueObjects\UserMfaBackupCodeIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class UserMfaBackupCodeTest extends TestCase
{
    public function testGenerateCreatesUnusedCode(): void
    {
        $code = UserMfaBackupCode::generate(
            new UserMfaBackupCodeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            'ABCD-1234-EFGH-5678',
        );

        self::assertFalse($code->isUsed());
        self::assertNull($code->usedAt());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $code->id());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FA1', (string) $code->userId());
        self::assertSame('ABCD-1234-EFGH-5678', $code->code());
        self::assertNotNull($code->createdAt());
    }

    public function testMarkAsUsedSetsUsedAt(): void
    {
        $code = UserMfaBackupCode::generate(
            new UserMfaBackupCodeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            'ABCD-1234-EFGH-5678',
        );

        $code->markAsUsed();

        self::assertTrue($code->isUsed());
        self::assertNotNull($code->usedAt());
    }

    public function testMarkAsUsedIsIdempotent(): void
    {
        $code = UserMfaBackupCode::generate(
            new UserMfaBackupCodeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            'ABCD-1234-EFGH-5678',
        );

        $code->markAsUsed();
        $code->markAsUsed();

        self::assertTrue($code->isUsed());
    }
}
