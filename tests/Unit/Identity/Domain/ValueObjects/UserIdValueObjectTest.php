<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserIdException;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class UserIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new UserIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(UserIdException::class);
        new UserIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = UserIdValueObject::fromString(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }
}
