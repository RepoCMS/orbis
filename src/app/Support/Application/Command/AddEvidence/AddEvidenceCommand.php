<?php

declare(strict_types=1);

namespace App\Support\Application\Command\AddEvidence;

final readonly class AddEvidenceCommand
{
    public function __construct(
        public string $ticketId,
        public string $filePath,
        public string $uploadedBy,
    ) {
    }
}
