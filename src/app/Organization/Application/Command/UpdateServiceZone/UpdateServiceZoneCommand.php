<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateServiceZone;

final readonly class UpdateServiceZoneCommand
{
    public function __construct(
        public string $serviceZoneId,
        public string $name,
    ) {
    }
}
