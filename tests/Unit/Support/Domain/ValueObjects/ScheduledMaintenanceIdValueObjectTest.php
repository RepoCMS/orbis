<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\ValueObjects;

use App\Support\Domain\Exceptions\ScheduledMaintenanceIdException;
use App\Support\Domain\ValueObjects\ScheduledMaintenanceIdValueObject;
use PHPUnit\Framework\TestCase;

final class ScheduledMaintenanceIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new ScheduledMaintenanceIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(ScheduledMaintenanceIdException::class);
        new ScheduledMaintenanceIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = ScheduledMaintenanceIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }
}
