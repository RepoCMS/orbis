<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\AuthLogException;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use PHPUnit\Framework\TestCase;

final class AuthLogIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new AuthLogIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(AuthLogException::class);
        new AuthLogIdValueObject('bad');
    }
}
