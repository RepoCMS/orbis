<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\TransferCity;

final readonly class TransferCityCommand
{
    public function __construct(
        public string $serviceZoneId,
        public string $cityId,
        public string $targetZoneId,
    ) {
    }
}
