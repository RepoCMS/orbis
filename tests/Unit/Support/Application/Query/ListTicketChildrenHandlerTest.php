<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support\Application\Query;

use App\Support\Application\Query\ListTicketChildren\ListTicketChildrenHandler;
use App\Support\Application\Query\ListTicketChildren\ListTicketChildrenQuery;
use App\Support\Application\Query\Response\TicketResponse;
use App\Support\Domain\Entities\Ticket;
use App\Support\Domain\Enums\ServiceType;
use App\Support\Domain\Repositories\TicketRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use PHPUnit\Framework\TestCase;

/**
 * @template T of object
 */
final class ListTicketChildrenHandlerTest extends TestCase
{
    private const PARENT_ID  = '01HZ0000000000000000000001';
    private const CLIENT_ID  = '01HZ0000000000000000000002';
    private const TERMINAL   = '01HZ0000000000000000000003';
    private const CREATED_BY = '01HZ0000000000000000000004';

    /**
     * @return TicketRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRepoWithMethod(string $method, mixed $return): mixed
    {
        $repo = new class () implements TicketRepositoryInterface {
            private array $methods = [];

            public function __call(string $name, array $args): mixed
            {
                return $this->methods[$name](...$args);
            }

            public function setReturn(string $method, mixed $return): void
            {
                $this->methods[$method] = is_callable($return) ? $return : fn () => $return;
            }

            public function findById(\App\Support\Domain\ValueObjects\TicketIdValueObject $id): ?Ticket
            {
                return null;
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

        $repo->setReturn($method, $return);

        return $repo;
    }

    public function testReturnsChildren(): void
    {
        $child = Ticket::create(
            id: TicketIdValueObject::fromString('01HZ000000000000000000000A'),
            ticketNumber: 'TK-2026-000001',
            clientId: self::CLIENT_ID,
            terminalAssignmentId: self::TERMINAL,
            serviceType: ServiceType::CORRECTIVO,
            description: 'Ticket hijo',
            createdBy: self::CREATED_BY,
        );

        $repo = $this->createRepoWithMethod('findByParentTicketId', [$child]);

        $handler = new ListTicketChildrenHandler($repo);
        $result = ($handler)(new ListTicketChildrenQuery(parentTicketId: self::PARENT_ID));

        self::assertCount(1, $result);
        self::assertInstanceOf(TicketResponse::class, $result[0]);
        self::assertSame('TK-2026-000001', $result[0]->ticketNumber);
    }

    public function testReturnsEmptyArrayWhenNoChildren(): void
    {
        $repo = $this->createRepoWithMethod('findByParentTicketId', []);

        $handler = new ListTicketChildrenHandler($repo);
        $result = ($handler)(new ListTicketChildrenQuery(parentTicketId: self::PARENT_ID));

        self::assertCount(0, $result);
    }
}
