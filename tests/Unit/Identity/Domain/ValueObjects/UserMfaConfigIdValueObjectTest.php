<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\TokenException;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use PHPUnit\Framework\TestCase;

final class UserMfaConfigIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new UserMfaConfigIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(TokenException::class);
        new UserMfaConfigIdValueObject('bad');
    }
}
