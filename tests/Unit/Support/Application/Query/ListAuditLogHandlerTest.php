<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListAuditLog\ListAuditLogHandler;
use App\Support\Application\Query\ListAuditLog\ListAuditLogQuery;
use App\Support\Domain\Entities\TicketAuditLog;
use App\Support\Domain\Repositories\TicketAuditLogRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAuditLogIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListAuditLogHandlerTest extends TestCase
{
    private const LOG_ID_1    = '01HZ0000000000000000000001';
    private const LOG_ID_2    = '01HZ0000000000000000000002';
    private const TICKET_ID   = '01HZ0000000000000000000003';

    public function testReturnsLogs(): void
    {
        $log1 = TicketAuditLog::record(
            id: TicketAuditLogIdValueObject::fromString(self::LOG_ID_1),
            ticketId: self::TICKET_ID,
            fieldName: 'status',
            oldValue: 'created',
            newValue: 'assigned',
            changedBy: 'user-1',
        );

        $log2 = TicketAuditLog::record(
            id: TicketAuditLogIdValueObject::fromString(self::LOG_ID_2),
            ticketId: self::TICKET_ID,
            fieldName: 'description',
            oldValue: 'original',
            newValue: 'updated',
            changedBy: 'user-2',
        );

        $auditLogRepo = $this->createMock(TicketAuditLogRepositoryInterface::class);
        $auditLogRepo->method('findByTicketId')->willReturn([$log1, $log2]);

        $handler = new ListAuditLogHandler($auditLogRepo);
        $result = ($handler)(new ListAuditLogQuery(ticketId: self::TICKET_ID));

        self::assertCount(2, $result);
        self::assertSame('status', $result[0]->fieldName);
        self::assertSame('description', $result[1]->fieldName);
        self::assertSame('created', $result[0]->oldValue);
        self::assertSame('assigned', $result[0]->newValue);
    }

    public function testReturnsEmptyWhenNoLogs(): void
    {
        $auditLogRepo = $this->createMock(TicketAuditLogRepositoryInterface::class);
        $auditLogRepo->method('findByTicketId')->willReturn([]);

        $handler = new ListAuditLogHandler($auditLogRepo);
        $result = ($handler)(new ListAuditLogQuery(ticketId: self::TICKET_ID));

        self::assertCount(0, $result);
    }
}
