<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\ValueObjects;

use App\Support\Domain\Exceptions\RmaRecordIdException;
use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;
use PHPUnit\Framework\TestCase;

final class RmaRecordIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new RmaRecordIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(RmaRecordIdException::class);
        new RmaRecordIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = RmaRecordIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }
}
