<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContractById;

use App\Client\Application\Exception\ContractNotFoundException;
use App\Client\Application\Query\Response\ContractResponse;
use App\Client\Domain\Repositories\ContractRepositoryInterface;
use App\Client\Domain\ValueObjects\ContractIdValueObject;

final readonly class FindContractByIdHandler
{
    public function __construct(
        private ContractRepositoryInterface $contractRepository,
    ) {
    }

    public function __invoke(FindContractByIdQuery $query): ContractResponse
    {
        $contract = $this->contractRepository->findById(new ContractIdValueObject($query->contractId))
            ?? throw ContractNotFoundException::withId($query->contractId);

        return ContractResponse::fromEntity($contract);
    }
}
