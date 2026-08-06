<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\ApproveExpenseItem;

use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ApproveExpenseItemHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private UserResolverInterface $userResolver,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(ApproveExpenseItemCommand $command): void
    {
        $this->userResolver->findActive($command->reviewedBy);

        $expenseId = new OperatingExpenseIdValueObject($command->expenseId);
        $expense = $this->expenseRepository->findById($expenseId);

        if ($expense === null) {
            throw new \InvalidArgumentException('Liquidación no encontrada');
        }

        $expense->approveItem($command->itemId, $command->reviewedBy);
        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
