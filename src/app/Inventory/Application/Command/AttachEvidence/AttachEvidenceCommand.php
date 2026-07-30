<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AttachEvidence;

final readonly class AttachEvidenceCommand
{
    public function __construct(
        public string $repairId,
        public string $filePath,
        public string $type,
        public string $uploadedBy,
        public ?string $notes = null,
    ) {
    }
}
