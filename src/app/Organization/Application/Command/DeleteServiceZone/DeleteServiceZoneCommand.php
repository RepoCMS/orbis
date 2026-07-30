<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeleteServiceZone;

final readonly class DeleteServiceZoneCommand
{
    public function __construct(
        public string $serviceZoneId,
    ) {
    }
}
