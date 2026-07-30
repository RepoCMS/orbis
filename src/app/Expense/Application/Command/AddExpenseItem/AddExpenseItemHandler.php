<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\AddExpenseItem;

use App\Expense\Domain\Entities\ExpenseItem;
use App\Expense\Domain\Enums\ExpenseItemType;
use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class AddExpenseItemHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(AddExpenseItemCommand $command): string
    {
        $expenseId = new OperatingExpenseIdValueObject($command->expenseId);
        $expense = $this->expenseRepository->findById($expenseId);

        if ($expense === null) {
            throw new \InvalidArgumentException('Liquidación no encontrada');
        }

        $item = ExpenseItem::create(
            id: new ExpenseItemIdValueObject($this->idGenerator->generate()),
            type: ExpenseItemType::from($command->type),
            amount: $command->amount,
            ticketIds: $command->ticketIds,
            notes: $command->notes,
        );

        $expense->addItem($item);
        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return (string) $item->id();
    }
}
