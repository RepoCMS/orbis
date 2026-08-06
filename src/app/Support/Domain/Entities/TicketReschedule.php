<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\Enums\RescheduleRequestedBy;
use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketRescheduleIdValueObject;

final class TicketReschedule
{
    public function __construct(
        private TicketRescheduleIdValueObject $id,
        private TicketIdValueObject           $ticketId,
        private \DateTimeImmutable            $previousScheduledAt,
        private \DateTimeImmutable            $newScheduledAt,
        private string                        $reason,
        private RescheduleRequestedBy         $requestedBy,
        private string                        $rescheduledBy,
        private \DateTimeImmutable            $rescheduledAt,
    ) {
    }

    public static function create(
        TicketRescheduleIdValueObject $id,
        TicketIdValueObject $ticketId,
        \DateTimeImmutable $previousScheduledAt,
        \DateTimeImmutable $newScheduledAt,
        string $reason,
        RescheduleRequestedBy $requestedBy,
        string $rescheduledBy,
    ): self {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('El motivo de la reprogramación es obligatorio.');
        }
        if (trim($rescheduledBy) === '') {
            throw new \InvalidArgumentException('El usuario que reprograma es obligatorio.');
        }
        if ($newScheduledAt <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('La nueva fecha debe ser en el futuro.');
        }

        return new self(
            id: $id,
            ticketId: $ticketId,
            previousScheduledAt: $previousScheduledAt,
            newScheduledAt: $newScheduledAt,
            reason: $reason,
            requestedBy: $requestedBy,
            rescheduledBy: $rescheduledBy,
            rescheduledAt: new \DateTimeImmutable(),
        );
    }

    public function id(): TicketRescheduleIdValueObject
    {
        return $this->id;
    }
    public function ticketId(): TicketIdValueObject
    {
        return $this->ticketId;
    }
    public function previousScheduledAt(): \DateTimeImmutable
    {
        return $this->previousScheduledAt;
    }
    public function newScheduledAt(): \DateTimeImmutable
    {
        return $this->newScheduledAt;
    }
    public function reason(): string
    {
        return $this->reason;
    }
    public function requestedBy(): RescheduleRequestedBy
    {
        return $this->requestedBy;
    }
    public function rescheduledBy(): string
    {
        return $this->rescheduledBy;
    }
    public function rescheduledAt(): \DateTimeImmutable
    {
        return $this->rescheduledAt;
    }
}
