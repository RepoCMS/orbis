<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AcceptByTechnician;

final readonly class AcceptByTechnicianCommand
{
    public function __construct(
        public string $requestId,
    ) {
    }
}
