<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\Contract;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContractIdValueObject;

interface ContractRepositoryInterface
{
    public function save(Contract $contract): void;

    public function findById(ContractIdValueObject $id): ?Contract;

    /**
     * @return Contract[]
     */
    public function findByClientId(ClientIdValueObject $clientId): array;

    /**
     * @return Contract[]
     */
    public function findAll(): array;
}
