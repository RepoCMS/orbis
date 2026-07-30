<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\CreateOperatingExpense;

use App\Expense\Application\Service\EmployeeResolverInterface;
use App\Expense\Application\Service\TicketResolverInterface;
use App\Expense\Domain\Entities\OperatingExpense;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Application\IdempotentHandlerTrait;
use App\Shared\Application\Service\IdempotencyService;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;

final readonly class CreateOperatingExpenseHandler
{
    use IdempotentHandlerTrait;

    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private IdGeneratorInterface $idGenerator,
        private EmployeeResolverInterface $employeeResolver,
        private TicketResolverInterface $ticketResolver,
        private DomainEventPublisherInterface $eventPublisher,
        private IdempotencyServiceInterface $idempotencyService,
    ) {}

    public function __invoke(CreateOperatingExpenseCommand $command): string
    {
        $this->ensureUnique(
            IdempotencyService::buildKey(CreateOperatingExpenseCommand::class, serialize($command)),
            CreateOperatingExpenseCommand::class,
            '',
        );
        $this->employeeResolver->findActive($command->employeeId);

        foreach ($command->ticketIds as $ticketId) {
            $this->ticketResolver->belongsToEmployee($ticketId, $command->employeeId);
        }

        $sequence = $this->expenseRepository->nextSequence($command->employeeId);

        $expense = OperatingExpense::create(
            id: new OperatingExpenseIdValueObject($this->idGenerator->generate()),
            sequence: $sequence,
            employeeId: $command->employeeId,
            reason: $command->reason,
            startDate: new DateTimeImmutable($command->startDate),
            endDate: new DateTimeImmutable($command->endDate),
            cashAdvanceId: $command->cashAdvanceId,
            ticketIds: $command->ticketIds,
        );

        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return (string) $expense->id();
    }
}
