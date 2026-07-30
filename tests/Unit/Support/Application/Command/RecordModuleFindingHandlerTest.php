<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Command;

use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use App\Support\Application\Command\RecordModuleFinding\RecordModuleFindingCommand;
use App\Support\Application\Command\RecordModuleFinding\RecordModuleFindingHandler;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Entities\TicketModuleFinding;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Events\TicketModuleFindingRecorded;
use App\Support\Domain\Repositories\TicketModuleFindingRepositoryInterface;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class RecordModuleFindingHandlerTest extends TestCase
{
    private const TICKET_ID     = '01HZ0000000000000000000001';
    private const FINDING_ID    = '01HZ0000000000000000000002';
    private const REGISTERED_BY = '01HZ0000000000000000000003';
    private const CLIENT_ID     = '01HZ0000000000000000000004';
    private const TERMINAL_ID   = '01HZ0000000000000000000005';
    private const CREATED_BY    = '01HZ0000000000000000000006';
    private const EMPLOYEE_ID   = '01HZ0000000000000000000007';
    private const ASSIGNMENT_ID = '01HZ0000000000000000000008';

    private function makeTicket(): Ticket
    {
        $ticketId = TicketIdValueObject::fromString(self::TICKET_ID);
        $ticket = Ticket::create(
            id: $ticketId,
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'ATM fuera de servicio',
            createdBy: self::CREATED_BY,
        );

        $assignment = TicketAssignment::create(
            id: TicketAssignmentIdValueObject::fromString(self::ASSIGNMENT_ID),
            ticketId: $ticketId,
            employeeId: self::EMPLOYEE_ID,
            isInCharge: true,
        );
        $ticket->assign([$assignment], new \DateTimeImmutable('+1 hour'));

        return $ticket;
    }

    public function testRecordsFindingSuccessfully(): void
    {
        $ticket = $this->makeTicket();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);
        $ticketRepo->expects($this->once())->method('save')->with($ticket);

        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $savedFinding = null;
        $findingRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (TicketModuleFinding $f) use (&$savedFinding): void {
                $savedFinding = $f;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::FINDING_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $publishedEvent = null;
        $eventPublisher->expects($this->exactly(3))
            ->method('publish')
            ->willReturnCallback(function (object ...$events) use (&$publishedEvent): void {
                foreach ($events as $event) {
                    if ($event instanceof TicketModuleFindingRecorded) {
                        $publishedEvent = $event;
                    }
                }
            });

        $handler = new RecordModuleFindingHandler(
            ticketRepo: $ticketRepo,
            findingRepo: $findingRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        $result = ($handler)(new RecordModuleFindingCommand(
            ticketId: self::TICKET_ID,
            modulePath: 'BRM > BMU > BC',
            componentName: 'Bill Validator',
            findingType: 'faulty',
            registeredBy: self::REGISTERED_BY,
            notes: 'El validador no reconoce billetes.',
        ));

        self::assertSame(self::FINDING_ID, $result);
        self::assertNotNull($savedFinding);
        self::assertSame('BRM > BMU > BC', $savedFinding->modulePath()->path);
        self::assertSame('Bill Validator', $savedFinding->componentName());
        self::assertSame('faulty', $savedFinding->findingType()->value);

        self::assertNotNull($publishedEvent);
        self::assertSame(self::FINDING_ID, $publishedEvent->findingId);
        self::assertSame(self::TICKET_ID, $publishedEvent->ticketId);
        self::assertSame('faulty', $publishedEvent->findingType);
    }

    public function testTicketNotFoundThrows(): void
    {
        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn(null);

        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RecordModuleFindingHandler(
            ticketRepo: $ticketRepo,
            findingRepo: $findingRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Ticket con ID "' . self::TICKET_ID . '" no encontrado.');

        ($handler)(new RecordModuleFindingCommand(
            ticketId: self::TICKET_ID,
            modulePath: 'BRM > BMU',
            componentName: 'Validator',
            findingType: 'faulty',
            registeredBy: self::REGISTERED_BY,
        ));
    }

    public function testInvalidFindingTypeThrows(): void
    {
        $ticket = $this->makeTicket();

        $ticketRepo = $this->createMock(TicketRepositoryInterface::class);
        $ticketRepo->method('findById')->willReturn($ticket);

        $findingRepo = $this->createMock(TicketModuleFindingRepositoryInterface::class);
        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::FINDING_ID);
        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new RecordModuleFindingHandler(
            ticketRepo: $ticketRepo,
            findingRepo: $findingRepo,
            idGenerator: $idGenerator,
            eventPublisher: $eventPublisher,
        );

        $this->expectException(\ValueError::class);

        ($handler)(new RecordModuleFindingCommand(
            ticketId: self::TICKET_ID,
            modulePath: 'BRM > BMU',
            componentName: 'Validator',
            findingType: 'invalid_finding_type',
            registeredBy: self::REGISTERED_BY,
        ));
    }
}
