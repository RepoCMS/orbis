<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\UpdateCashAdvance;

use App\Expense\Application\Exception\CashAdvanceNotFoundException;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class UpdateCashAdvanceHandler
{
    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UpdateCashAdvanceCommand $command): void
    {
        $cashAdvance = $this->cashAdvanceRepository->findById(
            new CashAdvanceIdValueObject($command->cashAdvanceId)
        );

        if ($cashAdvance === null) {
            throw new CashAdvanceNotFoundException($command->cashAdvanceId);
        }

        $cashAdvance->updateAmount($command->newAmount);

        $this->cashAdvanceRepository->save($cashAdvance);

        foreach ($cashAdvance->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
