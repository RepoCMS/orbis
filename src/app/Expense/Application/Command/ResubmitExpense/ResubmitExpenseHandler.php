<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ResubmitExpense;

use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ResubmitExpenseHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(ResubmitExpenseCommand $command): void
    {
        $expenseId = new OperatingExpenseIdValueObject($command->expenseId);
        $expense = $this->expenseRepository->findById($expenseId);

        if ($expense === null) {
            throw new \InvalidArgumentException('Liquidación no encontrada');
        }

        $expense->resubmit();
        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
