<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\ServiceZoneAssignmentIdException;
use App\Organization\Domain\ValueObjects\ServiceZoneAssignmentIdValueObject;
use PHPUnit\Framework\TestCase;

final class ServiceZoneAssignmentIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new ServiceZoneAssignmentIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(ServiceZoneAssignmentIdException::class);
        new ServiceZoneAssignmentIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = ServiceZoneAssignmentIdValueObject::fromString(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }
}
