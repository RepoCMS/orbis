<?php

declare(strict_types=1);

namespace App\Client\Application\Query\Response;

use App\Client\Domain\Entities\Contract;

final readonly class ContractResponse
{
    public function __construct(
        public string $id,
        public string $clientId,
        public string $startDate,
        public ?string $endDate,
        public ?string $terms,
        public ?string $scope,
        public string $status,
    ) {
    }

    public static function fromEntity(Contract $contract): self
    {
        return new self(
            id: (string) $contract->id(),
            clientId: (string) $contract->clientId(),
            startDate: $contract->startDate()->format('Y-m-d'),
            endDate: $contract->endDate()?->format('Y-m-d'),
            terms: $contract->terms(),
            scope: $contract->scope(),
            status: $contract->status()->value,
        );
    }
}
