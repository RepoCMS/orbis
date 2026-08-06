<?php

declare(strict_types=1);

namespace App\Client\Application\Command\CreateContract;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Contract;
use App\Client\Domain\Events\ContractCreated;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Shared\Application\IdempotentHandlerTrait;
use App\Shared\Application\Service\IdempotencyService;
use App\Shared\Application\Service\IdempotencyServiceInterface;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;

final readonly class CreateContractHandler
{
    use IdempotentHandlerTrait;

    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ContractRepositoryInterface $contractRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
        private IdempotencyServiceInterface $idempotencyService,
    ) {
    }

    public function __invoke(CreateContractCommand $command): void
    {
        $this->ensureUnique(
            IdempotencyService::buildKey(CreateContractCommand::class, serialize($command)),
            CreateContractCommand::class,
            '',
        );
        $clientId = new ClientIdValueObject($command->clientId);

        $client = $this->clientRepository->findById($clientId);
        if ($client === null) {
            throw ClientNotFoundException::withId($command->clientId);
        }

        $startDate = new DateTimeImmutable($command->startDate);
        $endDate = $command->endDate !== null ? new DateTimeImmutable($command->endDate) : null;

        $contract = Contract::create(
            id: new ContractIdValueObject($this->idGenerator->generate()),
            clientId: $clientId,
            startDate: $startDate,
            endDate: $endDate,
            terms: $command->terms,
            scope: $command->scope,
        );

        $this->contractRepository->save($contract);

        $this->eventPublisher->publish(new ContractCreated(
            contractId: (string) $contract->id(),
            clientId: $command->clientId,
        ));
    }
}
