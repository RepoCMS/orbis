<?php

declare(strict_types=1);

namespace App\Client\Application\Command\RegisterClient;

use App\Client\Application\Exception\ClientRucAlreadyExistsException;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RegisterClientHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(RegisterClientCommand $command): void
    {
        $ruc = new ClientRucValueObject($command->ruc);

        if ($this->clientRepository->findByRuc($ruc) !== null) {
            throw ClientRucAlreadyExistsException::withRuc($command->ruc);
        }

        $client = Client::register(
            id:      new ClientIdValueObject($this->idGenerator->generate()),
            name:    new ClientNameValueObject($command->name),
            ruc:     $ruc,
            address: $command->address,
        );

        $this->clientRepository->save($client);
    }
}
