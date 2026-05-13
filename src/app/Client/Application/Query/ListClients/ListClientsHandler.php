<?php

declare(strict_types=1);

namespace App\Client\Application\Query\ListClients;

use App\Client\Application\Query\Response\ClientResponse;
use App\Client\Domain\Criteria\ClientSearchCriteria;
use App\Client\Domain\Repositories\ClientRepositoryInterface;

final readonly class ListClientsHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {
    }

    /**
     * @return ClientResponse[]
     */
    public function __invoke(ListClientsQuery $query): array
    {
        $criteria = new ClientSearchCriteria(
            name: $query->name,
            ruc: $query->ruc,
            status: $query->status,
            page: $query->page,
            limit: $query->limit,
        );

        $clients = $this->clientRepository->search($criteria);

        return array_map(
            fn ($client) => ClientResponse::fromEntity($client),
            $clients
        );
    }
}
