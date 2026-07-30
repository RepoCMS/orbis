<?php

declare(strict_types=1);

namespace App\Tests\Unit\Expense\Application\Command;

use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseCommand;
use App\Expense\Application\Command\CreateOperatingExpense\CreateOperatingExpenseHandler;
use App\Expense\Application\Service\EmployeeResolverInterface;
use App\Expense\Application\Service\TicketResolverInterface;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Events\ExpenseSubmitted;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;
use PHPUnit\Framework\TestCase;

final class CreateOperatingExpenseHandlerTest extends TestCase
{
    private const EXPENSE_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testCreatesExpenseSuccessfully(): void
    {
        $repo = $this->createMock(OperatingExpenseRepositoryInterface::class);
        $repo->method('nextSequence')->willReturn(1);
        $repo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::EXPENSE_ID);

        $employeeResolver = $this->createMock(EmployeeResolverInterface::class);
        $ticketResolver = $this->createMock(TicketResolverInterface::class);
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(true);
        $publisher = $this->createMock(DomainEventPublisherInterface::class);

        $handler = new CreateOperatingExpenseHandler(
            $repo, $idGenerator, $employeeResolver, $ticketResolver, $publisher, $idempotency,
        );

        $result = ($handler)(new CreateOperatingExpenseCommand(
            employeeId: 'EMP001',
            reason: 'Gastos de viaje',
            startDate: '2026-01-01',
            endDate: '2026-01-15',
        ));

        self::assertSame(self::EXPENSE_ID, $result);
    }

    public function testThrowsWhenIdempotencyFails(): void
    {
        $idempotency = $this->createMock(IdempotencyServiceInterface::class);
        $idempotency->method('tryProcess')->willReturn(false);

        $handler = new CreateOperatingExpenseHandler(
            $this->createMock(OperatingExpenseRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(EmployeeResolverInterface::class),
            $this->createMock(TicketResolverInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $idempotency,
        );

        $this->expectException(ApplicationException::class);

        ($handler)(new CreateOperatingExpenseCommand(
            employeeId: 'EMP001',
            reason: 'Gastos',
            startDate: '2026-01-01',
            endDate: '2026-01-15',
        ));
    }
}
