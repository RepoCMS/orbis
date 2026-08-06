<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectTechnicianLoan;

use App\Inventory\Domain\Events\TechnicianLoanRejected;
use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class RejectTechnicianLoanHandler
{
    public function __construct(
        private TechnicianLoanRepositoryInterface $technicianLoanRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(RejectTechnicianLoanCommand $command): void
    {
        $loan = $this->technicianLoanRepository->findById(new TechnicianLoanIdValueObject($command->loanId));

        if ($loan === null) {
            throw new ApplicationException('El préstamo técnico no existe.');
        }

        if (!$loan->isPending()) {
            throw new InventoryDomainException('Solo se pueden rechazar préstamos en estado PENDING_LOAN.');
        }

        $loan->reject($command->reason, $command->rejectedBy);

        $this->technicianLoanRepository->save($loan);

        $this->publisher->publish(new TechnicianLoanRejected(
            loanId:     (string) $loan->id(),
            reason:     $command->reason,
            rejectedBy: $command->rejectedBy,
        ));
    }
}
