<?php

declare(strict_types=1);

namespace App\Expense\Application\Command\SettleCashAdvance;

use App\Expense\Application\Service\UserResolverInterface;
use App\Expense\Domain\Enums\SettlementDirection;
use App\Expense\Domain\Repositories\CashAdvanceRepositoryInterface;
use App\Expense\Domain\ValueObjects\CashAdvanceIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class SettleCashAdvanceHandler
{
    public function __construct(
        private CashAdvanceRepositoryInterface $cashAdvanceRepository,
        private UserResolverInterface $userResolver,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(SettleCashAdvanceCommand $command): void
    {
        $this->userResolver->findActive($command->settlementConfirmedBy);

        $cashAdvanceId = new CashAdvanceIdValueObject($command->cashAdvanceId);
        $cashAdvance = $this->cashAdvanceRepository->findById($cashAdvanceId);

        if ($cashAdvance === null) {
            throw new \InvalidArgumentException('Adelanto no encontrado');
        }

        $cashAdvance->settle(
            confirmedBy: $command->settlementConfirmedBy,
            direction: SettlementDirection::from($command->settlementDirection),
        );

        $this->cashAdvanceRepository->save($cashAdvance);

        foreach ($cashAdvance->pullDomainEvents() as $event) {
            $this->eventPublisher->publish($event);
        }
    }
}
