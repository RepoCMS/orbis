<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Support\Application\Command\ApplyQuickTemplate\ApplyQuickTemplateCommand;
use App\Support\Application\Command\ApplyQuickTemplate\ApplyQuickTemplateHandler;
use App\Support\Application\Exception\QuickTemplateNotFoundException;
use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Domain\Entities\QuickTemplate;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\QuickTemplateRepositoryInterface;
use App\Support\Domain\Repositories\TicketPendingSparePartRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\Repositories\TicketSparePartRepositoryInterface;
use App\Support\Domain\ValueObjects\QuickTemplateIdValueObject;
use App\Support\Domain\ValueObjects\QuickTemplateItemIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class ApplyQuickTemplateHandlerTest extends TestCase
{
    private const TEMPLATE_ID = '01HZ0000000000000000000001';
    private const TICKET_ID   = '01HZ0000000000000000000002';
    private const FINDING_ID  = '01HZ0000000000000000000003';
    private const CLIENT_ID   = '01HZ0000000000000000000004';
    private const TERMINAL_ID = '01HZ0000000000000000000005';
    private const CREATED_BY  = '01HZ0000000000000000000006';
    private const ITEM_ID_1   = '01HZ0000000000000000000007';
    private const ITEM_ID_2   = '01HZ0000000000000000000008';
    private const SPARE_ID_1  = '01HZ0000000000000000000009';
    private const SPARE_ID_2  = '01HZ0000000000000000000010';

    private function makeTemplate(): QuickTemplate
    {
        $template = QuickTemplate::create(
            id: QuickTemplateIdValueObject::fromString(self::TEMPLATE_ID),
            modelId: 'model-1',
            name: 'Plantilla de prueba',
            createdBy: self::CREATED_BY,
        );

        $template->addItem(
            id: QuickTemplateItemIdValueObject::fromString(self::ITEM_ID_1),
            modelPartId: null,
            partNumber: null,
            productCode: 'PROD-001',
            description: 'Producto con código',
            quantity: 2,
            notes: null,
            sortOrder: 0,
        );

        $template->addItem(
            id: QuickTemplateItemIdValueObject::fromString(self::ITEM_ID_2),
            modelPartId: null,
            partNumber: 'PN-001',
            productCode: null,
            description: 'Producto sin código',
            quantity: 1,
            notes: null,
            sortOrder: 1,
        );

        return $template;
    }

    private function makeTicket(): Ticket
    {
        return Ticket::create(
            id: TicketIdValueObject::fromString(self::TICKET_ID),
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );
    }

    public function testAppliesTemplateSuccessfully(): void
    {
        $template = $this->makeTemplate();
        $ticket   = $this->makeTicket();

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls(self::SPARE_ID_1, self::SPARE_ID_2);

        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $sparePartRepo->expects($this->once())->method('save');

        $pendingRepo = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $pendingRepo->expects($this->once())->method('save');

        $handler = new ApplyQuickTemplateHandler(
            $templateRepo,
            $ticketRepo,
            $sparePartRepo,
            $pendingRepo,
            $idGenerator,
        );

        $result = ($handler)(new ApplyQuickTemplateCommand(
            ticketId:   self::TICKET_ID,
            findingId:  self::FINDING_ID,
            templateId: self::TEMPLATE_ID,
        ));

        self::assertCount(1, $result['sparePartIds']);
        self::assertCount(1, $result['pendingSparePartIds']);
        self::assertSame(self::SPARE_ID_1, $result['sparePartIds'][0]);
        self::assertSame(self::SPARE_ID_2, $result['pendingSparePartIds'][0]);
    }

    public function testThrowsWhenTemplateNotFound(): void
    {
        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn(null);

        $ticketRepo    = $this->createMock(TicketRepositoryInterface::class);
        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $pendingRepo   = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $idGenerator   = $this->createMock(IdGeneratorInterface::class);

        $handler = new ApplyQuickTemplateHandler(
            $templateRepo,
            $ticketRepo,
            $sparePartRepo,
            $pendingRepo,
            $idGenerator,
        );

        $this->expectException(QuickTemplateNotFoundException::class);

        ($handler)(new ApplyQuickTemplateCommand(
            ticketId:   self::TICKET_ID,
            findingId:  self::FINDING_ID,
            templateId: '01HZ0000000000000000000099',
        ));
    }

    public function testThrowsWhenTicketNotFound(): void
    {
        $template = $this->makeTemplate();

        $templateRepo = $this->createMock(QuickTemplateRepositoryInterface::class);
        $templateRepo->method('findById')->willReturn($template);

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $sparePartRepo = $this->createMock(TicketSparePartRepositoryInterface::class);
        $pendingRepo   = $this->createMock(TicketPendingSparePartRepositoryInterface::class);
        $idGenerator   = $this->createMock(IdGeneratorInterface::class);

        $handler = new ApplyQuickTemplateHandler(
            $templateRepo,
            $ticketRepo,
            $sparePartRepo,
            $pendingRepo,
            $idGenerator,
        );

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new ApplyQuickTemplateCommand(
            ticketId:   '01HZ0000000000000000000099',
            findingId:  self::FINDING_ID,
            templateId: self::TEMPLATE_ID,
        ));
    }
}
