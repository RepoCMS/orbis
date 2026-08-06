<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final class TicketAssignment
{
    public function __construct(
        private TicketAssignmentIdValueObject $id,
        private TicketIdValueObject           $ticketId,
        private string                        $employeeId,
        private bool                          $isInCharge,
        private \DateTimeImmutable            $assignedAt,
        private ?string                       $reassignedFrom = null,
    ) {
    }

    public static function create(
        TicketAssignmentIdValueObject $id,
        TicketIdValueObject $ticketId,
        string $employeeId,
        bool $isInCharge,
        ?string $reassignedFrom = null,
    ): self {
        if (trim($employeeId) === '') {
            throw new \InvalidArgumentException('El ID del técnico no puede estar vacío.');
        }

        return new self(
            id: $id,
            ticketId: $ticketId,
            employeeId: $employeeId,
            isInCharge: $isInCharge,
            assignedAt: new \DateTimeImmutable(),
            reassignedFrom: $reassignedFrom,
        );
    }

    public function id(): TicketAssignmentIdValueObject
    {
        return $this->id;
    }
    public function ticketId(): TicketIdValueObject
    {
        return $this->ticketId;
    }
    public function employeeId(): string
    {
        return $this->employeeId;
    }
    public function isInCharge(): bool
    {
        return $this->isInCharge;
    }
    public function assignedAt(): \DateTimeImmutable
    {
        return $this->assignedAt;
    }
    public function reassignedFrom(): ?string
    {
        return $this->reassignedFrom;
    }
}
