<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketSparePart;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketSparePartIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketSparePartTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const FINDING_ID = '01HZ0000000000000000000003';

    public function testRegisterFactoryCreatesCorrectly(): void
    {
        $sparePart = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'MON-LCD-001',
            serialNumber: null,
            quantity: 2,
            isIncoming: true,
        );

        self::assertSame(self::ID, (string) $sparePart->id());
        self::assertSame(self::TICKET_ID, (string) $sparePart->ticketId());
        self::assertSame(self::FINDING_ID, (string) $sparePart->findingId());
        self::assertSame('MON-LCD-001', $sparePart->productCode());
        self::assertNull($sparePart->serialNumber());
        self::assertSame(2, $sparePart->quantity());
        self::assertTrue($sparePart->isIncoming());
        self::assertTrue($sparePart->isVisibleToClient());
        self::assertFalse($sparePart->autoMarkedDamaged());
    }

    public function testRegisterWithSerialNumber(): void
    {
        $sparePart = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'MON-LCD-001',
            serialNumber: 'SN-12345',
            quantity: 1,
            isIncoming: false,
            isVisibleToClient: false,
        );

        self::assertSame('SN-12345', $sparePart->serialNumber());
        self::assertFalse($sparePart->isIncoming());
        self::assertFalse($sparePart->isVisibleToClient());
    }

    public function testRegisterThrowsOnEmptyProductCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El código de producto es obligatorio');

        TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: '',
            serialNumber: null,
            quantity: 1,
            isIncoming: true,
        );
    }

    public function testRegisterThrowsOnQuantityLessThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La cantidad debe ser al menos 1');

        TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'MON-LCD-001',
            serialNumber: null,
            quantity: 0,
            isIncoming: true,
        );
    }

    public function testRegisterThrowsOnSerialWithQuantityNotOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un repuesto con serial siempre tiene cantidad 1');

        TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'MON-LCD-001',
            serialNumber: 'SN-12345',
            quantity: 2,
            isIncoming: true,
        );
    }

    public function testMarkAutoMarkedDamaged(): void
    {
        $sparePart = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'MON-LCD-001',
            serialNumber: null,
            quantity: 1,
            isIncoming: true,
        );

        $sparePart->markAutoMarkedDamaged();

        self::assertTrue($sparePart->autoMarkedDamaged());
    }

    public function testSetVisibleToClient(): void
    {
        $sparePart = TicketSparePart::register(
            id: TicketSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            findingId: TicketFindingIdValueObject::fromString(self::FINDING_ID),
            productCode: 'MON-LCD-001',
            serialNumber: null,
            quantity: 1,
            isIncoming: true,
        );

        $sparePart->setVisibleToClient(false);
        self::assertFalse($sparePart->isVisibleToClient());

        $sparePart->setVisibleToClient(true);
        self::assertTrue($sparePart->isVisibleToClient());
    }
}
