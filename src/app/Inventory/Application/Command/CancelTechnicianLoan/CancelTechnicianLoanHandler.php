<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelTechnicianLoan;

use App\Inventory\Domain\Events\TechnicianLoanCancelled;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class CancelTechnicianLoanHandler
{
    public function __construct(
        private TechnicianLoanRepositoryInterface $technicianLoanRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(CancelTechnicianLoanCommand $command): void
    {
        $loan = $this->technicianLoanRepository->findById(new TechnicianLoanIdValueObject($command->loanId));

        if ($loan === null) {
            throw new ApplicationException('El préstamo técnico no existe.');
        }

        if (!$loan->isPending()) {
            throw new InventoryDomainException('Solo se pueden cancelar préstamos en estado PENDING_LOAN.');
        }

        $loan->cancel($command->cancelledBy);

        $this->technicianLoanRepository->save($loan);

        $this->publisher->publish(new TechnicianLoanCancelled(
            loanId:      (string) $loan->id(),
            cancelledBy: $command->cancelledBy,
        ));
    }
}
