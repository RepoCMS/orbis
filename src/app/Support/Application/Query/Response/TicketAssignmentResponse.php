<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketAssignmentResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $employeeId,
        public bool    $isInCharge,
        public ?string $assignedAt,
        public ?string $reassignedFrom,
    ) {}

    public static function fromEntity(\App\Support\Domain\Entities\TicketAssignment $assignment): self
    {
        return new self(
            id: (string) $assignment->id(),
            ticketId: (string) $assignment->ticketId(),
            employeeId: $assignment->employeeId(),
            isInCharge: $assignment->isInCharge(),
            assignedAt: $assignment->assignedAt()->format('Y-m-d H:i'),
            reassignedFrom: $assignment->reassignedFrom(),
        );
    }
}
