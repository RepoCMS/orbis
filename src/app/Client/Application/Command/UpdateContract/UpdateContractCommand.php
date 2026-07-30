<?php

declare(strict_types=1);

namespace App\Client\Application\Command\UpdateContract;

final readonly class UpdateContractCommand
{
    public function __construct(
        public string $contractId,
        public ?string $terms = null,
        public ?string $scope = null,
        public ?string $endDate = null,
    ) {
    }
}
