<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RemoveCityFromZone;

final readonly class RemoveCityFromZoneCommand
{
    public function __construct(
        public string $serviceZoneId,
        public string $cityId,
    ) {
    }
}
