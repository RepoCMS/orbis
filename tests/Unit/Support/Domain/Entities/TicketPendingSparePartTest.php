<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketPendingSparePart;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketPendingSparePartTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';

    public function testRegisterFactoryCreatesCorrectly(): void
    {
        $pending = TicketPendingSparePart::register(
            id: TicketPendingSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            productCode: 'MON-LCD-001',
            quantity: 3,
        );

        self::assertSame(self::ID, (string) $pending->id());
        self::assertSame(self::TICKET_ID, (string) $pending->ticketId());
        self::assertSame('MON-LCD-001', $pending->productCode());
        self::assertSame(3, $pending->quantity());
        self::assertNull($pending->notes());
    }

    public function testRegisterWithNotes(): void
    {
        $pending = TicketPendingSparePart::register(
            id: TicketPendingSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            productCode: 'MON-LCD-001',
            quantity: 1,
            notes: 'Urgente - ATM fuera de servicio',
        );

        self::assertSame('Urgente - ATM fuera de servicio', $pending->notes());
    }

    public function testRegisterThrowsOnEmptyProductCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El código de producto es obligatorio');

        TicketPendingSparePart::register(
            id: TicketPendingSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            productCode: '',
            quantity: 1,
        );
    }

    public function testRegisterThrowsOnQuantityLessThanOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La cantidad debe ser al menos 1');

        TicketPendingSparePart::register(
            id: TicketPendingSparePartIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            productCode: 'MON-LCD-001',
            quantity: 0,
        );
    }
}
