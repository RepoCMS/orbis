<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Exception\TicketNotFoundException;
use App\Support\Application\Query\GetTicketParent\GetTicketParentHandler;
use App\Support\Application\Query\GetTicketParent\GetTicketParentQuery;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

final class GetTicketParentHandlerTest extends TestCase
{
    private const TICKET_ID   = '01HZ0000000000000000000001';
    private const PARENT_ID   = '01HZ0000000000000000000002';
    private const CLIENT_ID   = '01HZ0000000000000000000003';
    private const TERMINAL_ID = '01HZ0000000000000000000004';
    private const CREATED_BY  = '01HZ0000000000000000000005';

    public function testReturnsParentTicket(): void
    {
        $parent = Ticket::create(
            id: TicketIdValueObject::fromString(self::PARENT_ID),
            ticketNumber: 'TK-2026-PARENT',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL_ID,
            serviceType: ServiceType::CORRECTIVO,
            description: 'Parent ticket',
            createdBy: self::CREATED_BY,
        );

        $ticketRepo = $this->createRepoWithFindByIdCallback(
            fn (TicketIdValueObject $id): ?Ticket => match ((string) $id) {
                self::TICKET_ID => Ticket::create(
                    id: TicketIdValueObject::fromString(self::TICKET_ID),
                    ticketNumber: 'TK-2026-CHILD',
                    clientId: self::CLIENT_ID,
                    terminalAssignmentId: self::TERMINAL_ID,
                    serviceType: ServiceType::CORRECTIVO,
                    description: 'Child ticket',
                    createdBy: self::CREATED_BY,
                    parentTicketId: self::PARENT_ID,
                ),
                self::PARENT_ID => $parent,
                default => null,
            }
        );

        $handler = new GetTicketParentHandler($ticketRepo);
        $result = ($handler)(new GetTicketParentQuery(ticketId: self::TICKET_ID));

        self::assertNotNull($result);
        self::assertSame('TK-2026-PARENT', $result->ticketNumber);
        self::assertSame(self::CLIENT_ID, $result->clientId);
    }

    public function testReturnsNullWhenNoParent(): void
    {
        $ticketRepo = $this->createRepoWithFindByIdCallback(
            fn (TicketIdValueObject $id): Ticket => Ticket::create(
                id: TicketIdValueObject::fromString(self::TICKET_ID),
                ticketNumber: 'TK-2026-000001',
                clientId: self::CLIENT_ID,
                terminalAssignmentId: self::TERMINAL_ID,
                serviceType: ServiceType::CORRECTIVO,
                description: 'No parent ticket',
                createdBy: self::CREATED_BY,
            ),
        );

        $handler = new GetTicketParentHandler($ticketRepo);
        $result = ($handler)(new GetTicketParentQuery(ticketId: self::TICKET_ID));

        self::assertNull($result);
    }

    public function testThrowsExceptionWhenTicketNotFound(): void
    {
        $ticketRepo = $this->createRepoWithFindByIdCallback(fn (): ?Ticket => null);

        $handler = new GetTicketParentHandler($ticketRepo);

        $this->expectException(TicketNotFoundException::class);

        ($handler)(new GetTicketParentQuery(ticketId: self::TICKET_ID));
    }

    /**
     * @return TicketRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRepoWithFindByIdCallback(callable $callback): TicketRepositoryInterface
    {
        return new class ($callback) implements TicketRepositoryInterface {
            private \Closure $callback;

            public function __construct(callable $callback)
            {
                $this->callback = \Closure::fromCallable($callback);
            }

            public function findById(TicketIdValueObject $id): ?Ticket
            {
                return ($this->callback)($id);
            }

            public function findByTicketNumber(string $ticketNumber): ?Ticket
            {
                return null;
            }
            public function findByStatuses(array $statuses): array
            {
                return [];
            }
            public function findByClientId(string $clientId): array
            {
                return [];
            }
            public function findByEmployeeId(string $employeeId): array
            {
                return [];
            }
            public function findAll(): array
            {
                return [];
            }
            public function nextTicketSequence(int $year): int
            {
                return 0;
            }
            public function save(Ticket $ticket): void
            {
            }
            public function delete(TicketIdValueObject $id): void
            {
            }
        };
    }
}
