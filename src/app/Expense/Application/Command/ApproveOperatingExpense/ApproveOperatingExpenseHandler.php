<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ApproveOperatingExpense;

use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ApproveOperatingExpenseHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private UserResolverInterface $userResolver,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(ApproveOperatingExpenseCommand $command): void
    {
        $this->userResolver->findActive($command->approvedBy);

        $expenseId = new OperatingExpenseIdValueObject($command->expenseId);
        $expense = $this->expenseRepository->findById($expenseId);

        if ($expense === null) {
            throw new \InvalidArgumentException('Liquidación no encontrada');
        }

        $expense->approve($command->approvedBy);
        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
