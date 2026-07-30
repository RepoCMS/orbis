<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\Entities;

use App\Organization\Domain\Entities\ServiceZoneAssignment;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneAssignmentIdValueObject;
use App\Organization\Domain\ValueObjects\ServiceZoneIdValueObject;
use PHPUnit\Framework\TestCase;

final class ServiceZoneAssignmentTest extends TestCase
{
    private const ASSIGNMENT_ID = '01ASGN3NDEKTSV4RRFFQ69G5BB';
    private const EMPLOYEE_ID   = '01TECH3NDEKTSV4RRFFQ69G5AA';
    private const ZONE_ID       = '01ABCDEFGHJKMNPTQVZ0123456';
    private const PREV_ZONE_ID  = '01PREV3NDEKTSV4RRFFQ69G5CC';

    public function testCreatesAssignmentWithNewZoneOnly(): void
    {
        $assignment = ServiceZoneAssignment::create(
            id: new ServiceZoneAssignmentIdValueObject(self::ASSIGNMENT_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            newZoneId: new ServiceZoneIdValueObject(self::ZONE_ID),
        );

        self::assertSame(self::ASSIGNMENT_ID, (string) $assignment->id());
        self::assertSame(self::EMPLOYEE_ID, (string) $assignment->employeeId());
        self::assertSame(self::ZONE_ID, (string) $assignment->newZoneId());
        self::assertNull($assignment->previousZoneId());
        self::assertNull($assignment->assignedByUserId());
        self::assertNotNull($assignment->createdAt());
    }

    public function testCreatesAssignmentWithPreviousZone(): void
    {
        $assignment = ServiceZoneAssignment::create(
            id: new ServiceZoneAssignmentIdValueObject(self::ASSIGNMENT_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            newZoneId: new ServiceZoneIdValueObject(self::ZONE_ID),
            previousZoneId: new ServiceZoneIdValueObject(self::PREV_ZONE_ID),
        );

        self::assertNotNull($assignment->previousZoneId());
        self::assertSame(self::PREV_ZONE_ID, (string) $assignment->previousZoneId());
        self::assertSame(self::ZONE_ID, (string) $assignment->newZoneId());
    }

    public function testCreatesAssignmentWithAssignedBy(): void
    {
        $userId = '01USER3NDEKTSV4RRFFQ69G5DD';
        $assignment = ServiceZoneAssignment::create(
            id: new ServiceZoneAssignmentIdValueObject(self::ASSIGNMENT_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            newZoneId: new ServiceZoneIdValueObject(self::ZONE_ID),
            assignedByUserId: $userId,
        );

        self::assertSame($userId, $assignment->assignedByUserId());
    }

    public function testIsNotDeleted(): void
    {
        $assignment = ServiceZoneAssignment::create(
            id: new ServiceZoneAssignmentIdValueObject(self::ASSIGNMENT_ID),
            employeeId: new EmployeeIdValueObject(self::EMPLOYEE_ID),
            newZoneId: new ServiceZoneIdValueObject(self::ZONE_ID),
        );

        self::assertFalse($assignment->isDeleted());
        self::assertNull($assignment->deletedAt());

        // softDelete no debería tener efecto en el historial
        $assignment->softDelete();
        self::assertNull($assignment->deletedAt());
    }
}
