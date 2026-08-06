<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ApproveTechnicianLoan;

use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Events\TechnicianLoanApproved;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ApproveTechnicianLoanHandler
{
    public function __construct(
        private TechnicianLoanRepositoryInterface $technicianLoanRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(ApproveTechnicianLoanCommand $command): void
    {
        $loan = $this->technicianLoanRepository->findById(new TechnicianLoanIdValueObject($command->loanId));

        if ($loan === null) {
            throw new \InvalidArgumentException('El préstamo técnico no existe.');
        }

        if (!$loan->isPending()) {
            throw new \LogicException('Solo se pueden aprobar préstamos en estado PENDING_LOAN.');
        }

        // Look up the serialized stock to obtain product ID for the Movement
        $serializedStockId = new SerializedStockIdValueObject($loan->serializedStockId());
        $serializedStock = $this->serializedStockRepository->findById($serializedStockId);

        if ($serializedStock === null) {
            throw new \InvalidArgumentException('El stock serializado especificado no existe.');
        }

        // Approve the loan
        $loan->approve($command->approvedBy);

        // Create Movement(LOAN) for the serialized stock
        $movementId = $command->movementId !== null
            ? new MovementIdValueObject($command->movementId)
            : new MovementIdValueObject($this->idGenerator->generate());

        $movement = Movement::create(
            id:                   $movementId,
            productId:            $serializedStock->productId(),
            type:                 MovementType::LOAN,
            sourceLocationId:     null,
            destinationLocationId: null,
            serializedStockId:    $serializedStockId,
            quantity:             1,
            notes:                sprintf(
                'Préstamo de técnico %s a técnico %s. Loan ID: %s',
                $loan->fromTechnicianId(),
                $loan->toTechnicianId(),
                (string) $loan->id(),
            ),
        );

        // LOAN movements complete immediately
        $movement->complete();

        $this->technicianLoanRepository->save($loan);
        $this->movementRepository->save($movement);

        $this->publisher->publish(new TechnicianLoanApproved(
            loanId:            (string) $loan->id(),
            serializedStockId: $loan->serializedStockId(),
            fromTechnicianId:  $loan->fromTechnicianId(),
            toTechnicianId:    $loan->toTechnicianId(),
            approvedBy:        $command->approvedBy,
            movementId:        (string) $movement->id(),
        ));
    }
}
