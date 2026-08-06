<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\UpdateOperatingExpense;

use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;

final readonly class UpdateOperatingExpenseHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UpdateOperatingExpenseCommand $command): void
    {
        $expenseId = new OperatingExpenseIdValueObject($command->expenseId);
        $expense = $this->expenseRepository->findById($expenseId);

        if ($expense === null) {
            throw new \InvalidArgumentException('Liquidación no encontrada');
        }

        $expense->updateDetails(
            reason: $command->reason,
            startDate: new DateTimeImmutable($command->startDate),
            endDate: new DateTimeImmutable($command->endDate),
        );

        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
