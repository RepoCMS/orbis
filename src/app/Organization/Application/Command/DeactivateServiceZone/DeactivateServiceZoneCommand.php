<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeactivateServiceZone;

final readonly class DeactivateServiceZoneCommand
{
    public function __construct(
        public string $serviceZoneId,
    ) {
    }
}
