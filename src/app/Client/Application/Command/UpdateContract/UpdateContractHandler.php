<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateContract;

use App\Client\Application\Exception\ContractNotFoundException;
use App\Client\Domain\Events\ContractUpdated;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;

final readonly class UpdateContractHandler
{
    public function __construct(
        private ContractRepositoryInterface $contractRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(UpdateContractCommand $command): void
    {
        $contract = $this->contractRepository->findById(new ContractIdValueObject($command->contractId))
            ?? throw ContractNotFoundException::withId($command->contractId);

        if ($command->terms !== null) {
            $contract->updateTerms($command->terms);
        }

        if ($command->scope !== null) {
            $contract->updateScope($command->scope);
        }

        if ($command->endDate !== null) {
            $contract->extendEndDate(new DateTimeImmutable($command->endDate));
        }

        $this->contractRepository->save($contract);

        $this->eventPublisher->publish(new ContractUpdated(
            contractId: $command->contractId,
            clientId: (string) $contract->clientId(),
        ));
    }
}
