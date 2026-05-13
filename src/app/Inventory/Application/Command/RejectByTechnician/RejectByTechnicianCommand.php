<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectByTechnician;

final readonly class RejectByTechnicianCommand
{
    public function __construct(
        public string $requestId,
        public string $rejectedBy,
        public string $reason,
    ) {
    }
}
