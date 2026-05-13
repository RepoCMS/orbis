<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\AssignTechnicianToZone;

final readonly class AssignTechnicianToZoneCommand
{
    public function __construct(
        public string $technicianId,
        public string $serviceZoneId,
    ) {
    }
}
