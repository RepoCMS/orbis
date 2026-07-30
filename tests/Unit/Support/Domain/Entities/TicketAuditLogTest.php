<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketAuditLog;
use App\Support\Domain\ValueObjects\TicketAuditLogIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketAuditLogTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const CHANGED_BY = '01HZ0000000000000000000003';

    public function testRecordFactoryCreatesCorrectly(): void
    {
        $log = TicketAuditLog::record(
            id: TicketAuditLogIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            fieldName: 'status',
            oldValue: 'created',
            newValue: 'assigned',
            changedBy: self::CHANGED_BY,
        );

        self::assertSame(self::ID, (string) $log->id());
        self::assertSame(self::TICKET_ID, $log->ticketId());
        self::assertSame('status', $log->fieldName());
        self::assertSame('created', $log->oldValue());
        self::assertSame('assigned', $log->newValue());
        self::assertSame(self::CHANGED_BY, $log->changedBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $log->changedAt());
    }

    public function testRecordWithNullValues(): void
    {
        $log = TicketAuditLog::record(
            id: TicketAuditLogIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            fieldName: 'description',
            oldValue: null,
            newValue: 'Nueva descripción',
            changedBy: self::CHANGED_BY,
        );

        self::assertNull($log->oldValue());
        self::assertSame('Nueva descripción', $log->newValue());
    }

    public function testRecordThrowsOnEmptyTicketId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del ticket es obligatorio');

        TicketAuditLog::record(
            id: TicketAuditLogIdValueObject::fromString(self::ID),
            ticketId: '',
            fieldName: 'status',
            oldValue: null,
            newValue: 'assigned',
            changedBy: self::CHANGED_BY,
        );
    }

    public function testRecordThrowsOnEmptyFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El nombre del campo es obligatorio');

        TicketAuditLog::record(
            id: TicketAuditLogIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            fieldName: '',
            oldValue: null,
            newValue: 'assigned',
            changedBy: self::CHANGED_BY,
        );
    }

    public function testRecordThrowsOnEmptyChangedBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El usuario que realizó el cambio es obligatorio');

        TicketAuditLog::record(
            id: TicketAuditLogIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            fieldName: 'status',
            oldValue: null,
            newValue: 'assigned',
            changedBy: '',
        );
    }
}
