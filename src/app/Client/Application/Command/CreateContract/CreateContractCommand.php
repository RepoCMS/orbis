<?php

declare(strict_types=1);

namespace App\Client\Application\Command\CreateContract;

final readonly class CreateContractCommand
{
    public function __construct(
        public string $clientId,
        public string $startDate,
        public ?string $endDate = null,
        public ?string $terms = null,
        public ?string $scope = null,
    ) {
    }
}
