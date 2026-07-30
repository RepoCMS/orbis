<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\PermissionIdException;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use PHPUnit\Framework\TestCase;

final class PermissionIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new PermissionIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(PermissionIdException::class);
        new PermissionIdValueObject('bad');
    }
}
