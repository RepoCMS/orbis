<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Domain\Entities;

use App\Support\Domain\Entities\TicketFinding;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class TicketFindingTest extends TestCase
{
    private const ID = '01HZ0000000000000000000001';
    private const TICKET_ID = '01HZ0000000000000000000002';
    private const REGISTERED_BY = '01HZ0000000000000000000003';

    public function testRegisterFactoryCreatesCorrectly(): void
    {
        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::REGISTERED_BY,
        );

        self::assertSame(self::ID, (string) $finding->id());
        self::assertSame(self::TICKET_ID, (string) $finding->ticketId());
        self::assertSame('FALLA_DISPLAY', $finding->problemTypeFound());
        self::assertSame('Display LCD dañado', $finding->problemDetail());
        self::assertTrue($finding->batteryVoltageOk());
        self::assertSame('Reemplazo de display', $finding->solution());
        self::assertSame(self::REGISTERED_BY, $finding->registeredBy());
        self::assertNull($finding->catCode());
        self::assertNull($finding->physicalDocNumber());
    }

    public function testRegisterWithPhysicalDocNumber(): void
    {
        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA_DISPLAY',
            problemDetail: 'Display LCD dañado',
            batteryVoltageOk: true,
            solution: 'Reemplazo de display',
            registeredBy: self::REGISTERED_BY,
            physicalDocNumber: 'DOC-001',
        );

        self::assertSame('DOC-001', $finding->physicalDocNumber());
    }

    public function testRegisterThrowsOnEmptyProblemType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El tipo de problema encontrado es obligatorio');

        TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: '',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::REGISTERED_BY,
        );
    }

    public function testRegisterThrowsOnEmptyProblemDetail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El detalle del problema es obligatorio');

        TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: '',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::REGISTERED_BY,
        );
    }

    public function testRegisterThrowsOnEmptySolution(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La solución es obligatoria');

        TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: '',
            registeredBy: self::REGISTERED_BY,
        );
    }

    public function testRegisterThrowsOnEmptyRegisteredBy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El técnico que registra es obligatorio');

        TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: '',
        );
    }

    public function testAssignCatCodeSetsCatCode(): void
    {
        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::REGISTERED_BY,
        );

        $finding->assignCatCode('CAT-001');

        self::assertSame('CAT-001', $finding->catCode());
    }

    public function testCannotAssignCatCodeTwice(): void
    {
        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::REGISTERED_BY,
        );

        $finding->assignCatCode('CAT-001');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('El código CAT ya fue asignado');
        $finding->assignCatCode('CAT-002');
    }

    public function testUpdatePhysicalDocNumber(): void
    {
        $finding = TicketFinding::register(
            id: TicketFindingIdValueObject::fromString(self::ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            problemTypeFound: 'FALLA',
            problemDetail: 'Detalle',
            batteryVoltageOk: true,
            solution: 'Solución',
            registeredBy: self::REGISTERED_BY,
        );

        $finding->updatePhysicalDocNumber('DOC-002');
        self::assertSame('DOC-002', $finding->physicalDocNumber());

        $finding->updatePhysicalDocNumber(null);
        self::assertNull($finding->physicalDocNumber());
    }
}
