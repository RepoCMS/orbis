<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindClientById;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Query\Response\ClientResponse;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;

final readonly class FindClientByIdHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {
    }

    public function __invoke(FindClientByIdQuery $query): ClientResponse
    {
        $client = $this->clientRepository->findById(new ClientIdValueObject($query->clientId))
            ?? throw ClientNotFoundException::withId($query->clientId);

        return ClientResponse::fromEntity($client);
    }
}
