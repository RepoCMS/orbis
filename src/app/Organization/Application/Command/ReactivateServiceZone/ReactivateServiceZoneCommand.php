<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ReactivateServiceZone;

final readonly class ReactivateServiceZoneCommand
{
    public function __construct(
        public string $serviceZoneId,
    ) {
    }
}
