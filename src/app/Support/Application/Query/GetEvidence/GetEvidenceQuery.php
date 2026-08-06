<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetEvidence;

final readonly class GetEvidenceQuery
{
    public function __construct(
        public string $ticketId,
        public string $evidenceId,
    ) {
    }
}
