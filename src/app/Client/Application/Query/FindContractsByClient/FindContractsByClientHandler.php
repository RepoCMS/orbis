<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContractsByClient;

use App\Client\Application\Query\Response\ContractResponse;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;

final readonly class FindContractsByClientHandler
{
    public function __construct(
        private ContractRepositoryInterface $contractRepository,
    ) {
    }

    /**
     * @return ContractResponse[]
     */
    public function __invoke(FindContractsByClientQuery $query): array
    {
        return array_map(
            static fn($contract) => ContractResponse::fromEntity($contract),
            $this->contractRepository->findByClientId(new ClientIdValueObject($query->clientId)),
        );
    }
}
