<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Application\Command\UpdateModuleFinding;

use App\Shared\Exceptions\ApplicationException;
use App\Support\Application\Command\UpdateModuleFinding\UpdateModuleFindingCommand;
use App\Support\Application\Command\UpdateModuleFinding\UpdateModuleFindingHandler;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class UpdateModuleFindingHandlerTest extends TestCase
{
    private const FINDING_ID  = '01HZ00000000000000000000A1';
    private const TICKET_ID   = '01HZ00000000000000000000A2';
    private const REGISTERED  = '01HZ00000000000000000000A3';

    public function test_update_finding_type_successfully(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM'),
            componentName: 'Cassette',
            findingType: FindingType::FAULTY,
            registeredBy: self::REGISTERED,
        );

        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $findingRepo->method('findById')->willReturn($finding);
        $findingRepo->expects($this->once())->method('save');

        $handler = new UpdateModuleFindingHandler($findingRepo);

        ($handler)(new UpdateModuleFindingCommand(
            findingId: self::FINDING_ID,
            findingType: 'replaced',
            notes: 'Componente reemplazado en campo',
        ));

        self::assertSame(FindingType::REPLACED, $finding->findingType());
        self::assertSame('Componente reemplazado en campo', $finding->notes());
    }

    public function test_update_finding_clears_notes(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM'),
            componentName: 'Cassette',
            findingType: FindingType::PENDING,
            registeredBy: self::REGISTERED,
            notes: 'Nota anterior',
        );

        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $findingRepo->method('findById')->willReturn($finding);
        $findingRepo->expects($this->once())->method('save');

        $handler = new UpdateModuleFindingHandler($findingRepo);

        ($handler)(new UpdateModuleFindingCommand(
            findingId: self::FINDING_ID,
            findingType: 'ok',
            notes: null,
        ));

        self::assertSame(FindingType::OK, $finding->findingType());
        self::assertNull($finding->notes());
    }

    public function test_finding_not_found_throws(): void
    {
        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $findingRepo->method('findById')->willReturn(null);

        $handler = new UpdateModuleFindingHandler($findingRepo);

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Module finding con ID "01HZ00000000000000000000ZZ" no encontrado.');

        ($handler)(new UpdateModuleFindingCommand(
            findingId: '01HZ00000000000000000000ZZ',
            findingType: 'ok',
        ));
    }

    public function test_invalid_finding_type_throws(): void
    {
        $finding = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString(self::FINDING_ID),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM'),
            componentName: 'Cassette',
            findingType: FindingType::OK,
            registeredBy: self::REGISTERED,
        );

        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $findingRepo->method('findById')->willReturn($finding);

        $handler = new UpdateModuleFindingHandler($findingRepo);

        $this->expectException(\ValueError::class);

        ($handler)(new UpdateModuleFindingCommand(
            findingId: self::FINDING_ID,
            findingType: 'invalid_type',
        ));
    }
}
