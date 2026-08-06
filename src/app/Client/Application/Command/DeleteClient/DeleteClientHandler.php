<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeleteClient;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;

final readonly class DeleteClientHandler
{
    public function __construct(private ClientRepositoryInterface $clientRepository)
    {
    }

    public function __invoke(DeleteClientCommand $command): void
    {
        $clientId = new ClientIdValueObject($command->clientId);
        $client = $this->clientRepository->findById($clientId)
            ?? throw ClientNotFoundException::withId($command->clientId);

        $client->softDelete();

        $this->clientRepository->save($client);
    }
}
