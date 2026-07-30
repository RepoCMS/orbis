<?php

declare(strict_types=1);

namespace App\Client\Application\Query\Response;

use App\Client\Domain\Entities\TerminalAssignment;

final readonly class TerminalAssignmentResponse
{
    public function __construct(
        public string $id,
        public string $terminalId,
        public string $locationId,
        public string $name,
        public string $idAssignedByClient,
        public string $status,
    ) {
    }

    public static function fromEntity(TerminalAssignment $assignment): self
    {
        return new self(
            id:                  (string) $assignment->id(),
            terminalId:          (string) $assignment->terminalId(),
            locationId:          (string) $assignment->siteId(),
            name:                (string) $assignment->name(),
            idAssignedByClient:  (string) $assignment->idAssingedByClient(),
            status:              $assignment->status()->value,
        );
    }
}
