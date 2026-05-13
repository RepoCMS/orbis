<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AddCityToZone;

final readonly class AddCityToZoneCommand
{
    public function __construct(
        public string $serviceZoneId,
        public string $cityId,
    ) {
    }
}
