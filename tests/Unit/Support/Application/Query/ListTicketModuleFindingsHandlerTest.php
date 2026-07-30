<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListTicketModuleFindings\ListTicketModuleFindingsHandler;
use App\Support\Application\Query\ListTicketModuleFindings\ListTicketModuleFindingsQuery;
use App\Support\Domain\AtmModel\FindingType;
use App\Support\Domain\AtmModel\ModulePathValueObject;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListTicketModuleFindingsHandlerTest extends TestCase
{
    private const TICKET_ID = '01HZ0000000000000000000001';

    public function testReturnsFindingsForTicket(): void
    {
        $finding1 = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString('01HZ000000000000000000000A'),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU > BC'),
            componentName: 'Componente A',
            findingType: FindingType::FAULTY,
            registeredBy: 'user-1',
        );

        $finding2 = TicketModuleFinding::record(
            id: TicketModuleFindingIdValueObject::fromString('01HZ000000000000000000000B'),
            ticketId: TicketIdValueObject::fromString(self::TICKET_ID),
            modulePath: new ModulePathValueObject('BRM > BMU'),
            componentName: 'Componente B',
            findingType: FindingType::OK,
            registeredBy: 'user-1',
        );

        $repo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $repo->method('findByTicket')->willReturn([$finding1, $finding2]);

        $handler = new ListTicketModuleFindingsHandler($repo);
        $result = ($handler)(new ListTicketModuleFindingsQuery(ticketId: self::TICKET_ID));

        self::assertCount(2, $result);
        self::assertSame('Componente A', $result[0]->componentName);
        self::assertSame(FindingType::FAULTY->value, $result[0]->findingType);
        self::assertSame('Componente B', $result[1]->componentName);
        self::assertSame(FindingType::OK->value, $result[1]->findingType);
    }

    public function testReturnsEmptyArrayWhenNoFindings(): void
    {
        $repo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $repo->method('findByTicket')->willReturn([]);

        $handler = new ListTicketModuleFindingsHandler($repo);
        $result = ($handler)(new ListTicketModuleFindingsQuery(ticketId: self::TICKET_ID));

        self::assertCount(0, $result);
    }
}
