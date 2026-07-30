<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreateTechnicianLoan;

use App\Inventory\Domain\Entities\TechnicianLoan;
use App\Inventory\Domain\Events\TechnicianLoanRequested;
use App\Inventory\Domain\Repositories\TechnicianLoanRepositoryInterface;
use App\Inventory\Domain\ValueObjects\TechnicianLoanIdValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class CreateTechnicianLoanHandler
{
    public function __construct(
        private TechnicianLoanRepositoryInterface $technicianLoanRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $publisher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CreateTechnicianLoanCommand $command): void
    {
        $this->transactionManager->begin();

        try {
            $id = new TechnicianLoanIdValueObject($this->idGenerator->generate());

            $loan = TechnicianLoan::request(
                id:                $id,
                serializedStockId: $command->serializedStockId,
                fromTechnicianId:  $command->fromTechnicianId,
                toTechnicianId:    $command->toTechnicianId,
                ticketId:          $command->ticketId,
                notes:             $command->notes,
            );

            $this->technicianLoanRepository->save($loan);

            $this->publisher->publish(new TechnicianLoanRequested(
                loanId:            (string) $loan->id(),
                serializedStockId: $loan->serializedStockId(),
                fromTechnicianId:  $loan->fromTechnicianId(),
                toTechnicianId:    $loan->toTechnicianId(),
                ticketId:          $loan->ticketId(),
            ));

            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();

            throw new ApplicationException('Error al procesar la solicitud.', 0, $e);
        }
    }
}
