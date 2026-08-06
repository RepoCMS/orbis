<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\DeleteOperatingExpense;

use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class DeleteOperatingExpenseHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(DeleteOperatingExpenseCommand $command): void
    {
        $expenseId = new OperatingExpenseIdValueObject($command->expenseId);
        $expense = $this->expenseRepository->findById($expenseId);

        if ($expense === null) {
            throw new \InvalidArgumentException('Liquidación no encontrada');
        }

        $expense->softDelete();
        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
