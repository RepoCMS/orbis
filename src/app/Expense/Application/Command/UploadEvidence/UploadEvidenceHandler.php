<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\UploadEvidence;

use App\Expense\Domain\Repositories\OperatingExpenseRepositoryInterface;
use App\Expense\Domain\ValueObjects\ExpenseItemIdValueObject;
use App\Expense\Domain\ValueObjects\OperatingExpenseIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class UploadEvidenceHandler
{
    public function __construct(
        private OperatingExpenseRepositoryInterface $expenseRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {}

    public function __invoke(UploadEvidenceCommand $command): string
    {
        $expenseId = new OperatingExpenseIdValueObject($command->expenseId);
        $expense = $this->expenseRepository->findById($expenseId);

        if ($expense === null) {
            throw new \InvalidArgumentException('Liquidación no encontrada');
        }

        $itemId = $command->itemId;
        $item = null;
        foreach ($expense->items() as $i) {
            if ((string) $i->id() === $itemId) {
                $item = $i;
                break;
            }
        }

        if ($item === null) {
            throw new \InvalidArgumentException('Item de gasto no encontrado');
        }

        $item->uploadEvidence($command->filePath);
        $this->expenseRepository->save($expense);

        foreach ($expense->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }

        return $command->filePath;
    }
}
