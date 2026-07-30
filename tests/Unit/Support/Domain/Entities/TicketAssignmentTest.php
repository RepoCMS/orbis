<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketAssignmentTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const EMPLOYEE_ID = '01HZ0000000000000000000003';

    public function testCreateFactoryCreatesCorrectly(): void
    {
        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            employeeId: self::EMPLOYEE_ID,
            isInCharge: true,
        );

        self::assertSame(self::ID, (string) $assignment->id());
        self::assertSame(self::TICKET_ID, (string) $assignment->ticketId());
        self::assertSame(self::EMPLOYEE_ID, $assignment->employeeId());
        self::assertTrue($assignment->isInCharge());
        self::assertNull($assignment->reassignedFrom());
        self::assertInstanceOf(\DateTimeImmutable::class, $assignment->assignedAt());
    }

    public function testCreateWithReassignedFrom(): void
    {
        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            employeeId: self::EMPLOYEE_ID,
            isInCharge: false,
            reassignedFrom: '01HZ0000000000000000000004',
        );

        self::assertFalse($assignment->isInCharge());
        self::assertSame('01HZ0000000000000000000004', $assignment->reassignedFrom());
    }

    public function testCreateThrowsOnEmptyEmployeeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del técnico no puede estar vacío');

        TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            employeeId: '',
            isInCharge: true,
        );
    }
}
