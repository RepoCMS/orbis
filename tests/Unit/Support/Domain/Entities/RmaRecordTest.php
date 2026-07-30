<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\RmaRecord;
use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;
use PHPUnit\Framework\TestCase;

final class RmaRecordTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const CREATED_BY = '01HZ0000000000000000000003';

    public function testCreateFactoryCreatesCorrectly(): void
    {
        $rma = RmaRecord::create(
            id: RmaRecordIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            productCode: 'MON-LCD-001',
            createdBy: self::CREATED_BY,
        );

        self::assertSame(self::ID, (string) $rma->id());
        self::assertSame(self::TICKET_ID, $rma->ticketId());
        self::assertSame('MON-LCD-001', $rma->productCode());
        self::assertNull($rma->serialNumber());
        self::assertSame(1, $rma->quantity());
        self::assertNull($rma->notes());
        self::assertSame(self::CREATED_BY, $rma->createdBy());
        self::assertInstanceOf(\DateTimeImmutable::class, $rma->createdAt());
        self::assertFalse($rma->isDeleted());
    }

    public function testCreateWithOptionalParams(): void
    {
        $rma = RmaRecord::create(
            id: RmaRecordIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            productCode: 'MON-LCD-001',
            createdBy: self::CREATED_BY,
            serialNumber: 'SN-12345',
            quantity: 3,
            notes: 'Repuestos dañados',
        );

        self::assertSame('SN-12345', $rma->serialNumber());
        self::assertSame(3, $rma->quantity());
        self::assertSame('Repuestos dañados', $rma->notes());
    }

    public function testCreateThrowsOnEmptyTicketId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El ID del ticket es obligatorio');

        RmaRecord::create(
            id: RmaRecordIdValueObject::fromString(self::ID),
            ticketId: '',
            productCode: 'MON-LCD-001',
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyProductCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El código de producto es obligatorio');

        RmaRecord::create(
            id: RmaRecordIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            productCode: '',
            createdBy: self::CREATED_BY,
        );
    }

    public function testCreateThrowsOnEmptyCreatedBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El usuario creador es obligatorio');

        RmaRecord::create(
            id: RmaRecordIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            productCode: 'MON-LCD-001',
            createdBy: '',
        );
    }

    public function testCreateThrowsOnQuantityLessThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La cantidad debe ser al menos 1');

        RmaRecord::create(
            id: RmaRecordIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            productCode: 'MON-LCD-001',
            createdBy: self::CREATED_BY,
            quantity: 0,
        );
    }

    public function testSoftDelete(): void
    {
        $rma = RmaRecord::create(
            id: RmaRecordIdValueObject::fromString(self::ID),
            ticketId: self::TICKET_ID,
            productCode: 'MON-LCD-001',
            createdBy: self::CREATED_BY,
        );

        $rma->softDelete();

        self::assertTrue($rma->isDeleted());
        self::assertNotNull($rma->deletedAt());
    }
}
