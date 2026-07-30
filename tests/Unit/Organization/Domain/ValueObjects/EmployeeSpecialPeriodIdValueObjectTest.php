<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodIdException;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use PHPUnit\Framework\TestCase;

final class EmployeeSpecialPeriodIdValueObjectTest extends TestCase
{
    private const VALID_ULID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testAcceptsValidUlid(): void
    {
        $vo = new EmployeeSpecialPeriodIdValueObject(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(EmployeeSpecialPeriodIdException::class);
        new EmployeeSpecialPeriodIdValueObject('not-a-valid-ulid');
    }

    public function testFromStringFactory(): void
    {
        $vo = EmployeeSpecialPeriodIdValueObject::fromString(self::VALID_ULID);
        self::assertSame(self::VALID_ULID, (string) $vo);
    }
}
