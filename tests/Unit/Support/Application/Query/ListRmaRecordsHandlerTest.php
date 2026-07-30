<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListRmaRecords\ListRmaRecordsHandler;
use App\Support\Application\Query\ListRmaRecords\ListRmaRecordsQuery;
use App\Support\Domain\Entities\RmaRecord;
use App\Support\Domain\Repositories\RmaRecordRepositoryInterface;
use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListRmaRecordsHandlerTest extends TestCase
{
    private const RECORD_ID_1 = '01HZ0000000000000000000001';
    private const RECORD_ID_2 = '01HZ0000000000000000000002';
    private const TICKET_ID   = '01HZ0000000000000000000003';
    private const CREATED_BY  = '01HZ0000000000000000000004';

    private function makeRecord(string $id): RmaRecord
    {
        return RmaRecord::create(
            id: RmaRecordIdValueObject::fromString($id),
            ticketId: self::TICKET_ID,
            productCode: 'PROD-00' . substr($id, -1),
            createdBy: self::CREATED_BY,
            serialNumber: 'SN-00' . substr($id, -1),
            quantity: 1,
        );
    }

    public function testReturnsRecords(): void
    {
        $records = [
            $this->makeRecord(self::RECORD_ID_1),
            $this->makeRecord(self::RECORD_ID_2),
        ];

        $rmaRepo = $this->createMock(RmaRecordRepositoryInterface::class);
        $rmaRepo->method('findAll')->willReturn($records);

        $handler = new ListRmaRecordsHandler($rmaRepo);
        $result = ($handler)(new ListRmaRecordsQuery());

        self::assertCount(2, $result);
        self::assertSame('PROD-001', $result[0]->productCode);
        self::assertSame('PROD-002', $result[1]->productCode);
    }

    public function testReturnsEmptyWhenNoRecords(): void
    {
        $rmaRepo = $this->createMock(RmaRecordRepositoryInterface::class);
        $rmaRepo->method('findAll')->willReturn([]);

        $handler = new ListRmaRecordsHandler($rmaRepo);
        $result = ($handler)(new ListRmaRecordsQuery());

        self::assertCount(0, $result);
    }

    public function testFiltersByTicketId(): void
    {
        $record = $this->makeRecord(self::RECORD_ID_1);

        $rmaRepo = $this->createMock(RmaRecordRepositoryInterface::class);
        $rmaRepo->method('findByTicketId')->willReturn([$record]);

        $handler = new ListRmaRecordsHandler($rmaRepo);
        $result = ($handler)(new ListRmaRecordsQuery(ticketId: self::TICKET_ID));

        self::assertCount(1, $result);
        self::assertSame(self::TICKET_ID, $result[0]->ticketId);
    }

    public function testExcludesDeletedRecords(): void
    {
        $record1 = $this->makeRecord(self::RECORD_ID_1);
        $record2 = $this->makeRecord(self::RECORD_ID_2);
        $record2->softDelete();

        $rmaRepo = $this->createMock(RmaRecordRepositoryInterface::class);
        $rmaRepo->method('findAll')->willReturn([$record1, $record2]);

        $handler = new ListRmaRecordsHandler($rmaRepo);
        $result = ($handler)(new ListRmaRecordsQuery());

        self::assertCount(1, $result);
        self::assertSame(self::RECORD_ID_1, $result[0]->id);
    }
}
