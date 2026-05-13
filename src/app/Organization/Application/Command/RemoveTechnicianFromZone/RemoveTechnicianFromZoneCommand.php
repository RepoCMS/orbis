<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RemoveTechnicianFromZone;

final readonly class RemoveTechnicianFromZoneCommand
{
    public function __construct(
        public string $technicianId,
    ) {
    }
}
