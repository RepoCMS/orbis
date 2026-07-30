<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RemoveEvidence;

final readonly class RemoveEvidenceCommand
{
    public function __construct(
        public string $repairId,
        public string $evidenceId,
    ) {
    }
}
