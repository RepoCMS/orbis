<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateServiceZone;

final readonly class CreateServiceZoneCommand
{
    public function __construct(
        public string $name,
    ) {
    }
}
