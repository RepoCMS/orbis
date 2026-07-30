<?php

declare(strict_types=1);

namespace App\Client\Application\Command\CancelContract;

use App\Client\Application\Exception\ContractNotFoundException;
use App\Client\Domain\Events\ContractCancelled;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class CancelContractHandler
{
    public function __construct(
        private ContractRepositoryInterface $contractRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(CancelContractCommand $command): void
    {
        $contract = $this->contractRepository->findById(new ContractIdValueObject($command->contractId))
            ?? throw ContractNotFoundException::withId($command->contractId);

        $contract->cancel();

        $this->contractRepository->save($contract);

        $this->eventPublisher->publish(new ContractCancelled(
            contractId: $command->contractId,
            clientId: (string) $contract->clientId(),
        ));
    }
}
