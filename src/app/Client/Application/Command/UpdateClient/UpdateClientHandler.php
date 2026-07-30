<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateClient;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Shared\Application\Service\TransactionManagerInterface;

final readonly class UpdateClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(UpdateClientCommand $command): void
    {
        $this->transactionManager->begin();
        try {
            $clientId = new ClientIdValueObject($command->clientId);
            $client   = $this->clientRepository->findById($clientId)
                ?? throw ClientNotFoundException::withId($command->clientId);

            $client->changeName(new ClientNameValueObject($command->name));
            $client->changeAddress($command->address);

            $this->clientRepository->save($client);
            $this->transactionManager->commit();
        } catch (\Throwable $e) {
            $this->transactionManager->rollback();
            throw $e;
        }
    }
}
